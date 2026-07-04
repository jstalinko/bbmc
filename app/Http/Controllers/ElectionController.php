<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Calon;
use App\Models\Otp;
use App\Models\Polling;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ElectionController extends Controller
{
    /**
     * Helper to read election settings from JSON.
     */
    private function getSettings()
    {
        $path = storage_path('app/private/pemilihan-setting.json');

        if (!file_exists($path)) {
            return [
                'ajukan_diri' => true,
                'ajukan_anggota' => true,
                'tanggal_mulai' => null,
                'tanggal_selesai' => null,
                'piwapi_api_secret_key' => null,
                'piwapi_account_id' => null,
            ];
        }
        $settings = json_decode(file_get_contents($path), true);
        return [
            'ajukan_diri' => $settings['ajukan_diri'] ?? true,
            'ajukan_anggota' => $settings['ajukan_anggota'] ?? true,
            'tanggal_mulai' => $settings['tanggal_mulai'] ?? null,
            'tanggal_selesai' => $settings['tanggal_selesai'] ?? null,
            'piwapi_api_secret_key' => $settings['piwapi_api_secret_key'] ?? null,
            'piwapi_account_id' => $settings['piwapi_account_id'] ?? null,
        ];
    }

    /**
     * Helper to check if current time is within election window.
     */
    private function checkElectionTimeline()
    {
        $settings = $this->getSettings();
        $now = now();
        if ($settings['tanggal_mulai'] && $now->lt(\Carbon\Carbon::parse($settings['tanggal_mulai']))) {
            return [
                'allowed' => false,
                'message' => 'Halaman login pemilihan belum dapat diakses sebelum tanggal mulai pemilihan (' . \Carbon\Carbon::parse($settings['tanggal_mulai'])->translatedFormat('d F Y H:i') . ').'
            ];
        }
        if ($settings['tanggal_selesai'] && $now->gt(\Carbon\Carbon::parse($settings['tanggal_selesai']))) {
            return [
                'allowed' => false,
                'message' => 'Pemilihan telah selesai pada (' . \Carbon\Carbon::parse($settings['tanggal_selesai'])->translatedFormat('d F Y H:i') . ').'
            ];
        }
        return ['allowed' => true];
    }

    /**
     * Helper to check if current time is after start date for polling page access.
     */
    private function checkPollingTimeline()
    {
        $settings = $this->getSettings();
        $now = now();
        if ($settings['tanggal_mulai'] && $now->lt(\Carbon\Carbon::parse($settings['tanggal_mulai']))) {
            return [
                'allowed' => false,
                'message' => 'Halaman hasil pemilihan belum dapat diakses sebelum tanggal mulai pemilihan (' . \Carbon\Carbon::parse($settings['tanggal_mulai'])->translatedFormat('d F Y H:i') . ').'
            ];
        }
        return ['allowed' => true];
    }

    /**
     * Render the secure election login page.
     */
    public function login(Request $request)
    {
        $timeline = $this->checkElectionTimeline();
        if (!$timeline['allowed']) {
            return redirect()->route('election.portal')->with('error', $timeline['message']);
        }

        if ($request->session()->has('election_member_id')) {
            return redirect()->route('election.dashboard');
        }
        return Inertia::render('Election/login');
    }

    /**
     * Send OTP for member login verification.
     */
    public function sendLoginOtp(Request $request)
    {
        $timeline = $this->checkElectionTimeline();
        if (!$timeline['allowed']) {
            return response()->json([
                'success' => false,
                'message' => $timeline['message']
            ], 403);
        }

        $request->validate([
            'no_kartu' => 'required|string',
        ]);

        $nocard = str_pad($request->no_kartu, 4, '0', STR_PAD_LEFT);
        $member = Member::where('no_kartu', $nocard)->first();

        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => "Anggota dengan No. Kartu $nocard tidak ditemukan di database."
            ], 404);
        }

        $status = strtoupper($member->status_keanggotaan);
        if ($status !== 'LIFE MEMBER' && $status !== 'SS DIPONEGORO') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya anggota dengan status LIFE MEMBER atau SS DIPONEGORO yang dapat masuk.'
            ], 403);
        }

        // Check if already voted
        if (Polling::where('member_id', $member->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Anda sudah menyalurkan hak suara Anda dan tidak dapat masuk kembali.'
            ], 403);
        }

        if (!$member->no_wa) {
            return response()->json([
                'success' => false,
                'message' => "Nomor WhatsApp anggota tidak ditemukan di database."
            ], 400);
        }

        $otpCode = (string) rand(100000, 999999);
        
        Otp::create([
            'member_id' => $member->id,
            'otp' => $otpCode,
            'phone' => $member->no_wa,
            'expires_at' => now()->addMinutes(5),
            'is_verified' => false,
        ]);

        $message = "*BBMC ELECTION 2026*\n\nKode OTP Anda untuk proses login portal adalah: *$otpCode*\n\nBerlaku selama 5 menit. JANGAN BERIKAN KODE INI KEPADA SIAPAPUN.";
        
        \App\Helper::sendWhatsapp($member->no_wa, $message);

        return response()->json([
            'success' => true,
            'message' => 'OTP telah dikirim ke nomor WhatsApp Anda.'
        ]);
    }

    /**
     * Handle member login with OTP.
     */
    public function loginPost(Request $request)
    {
        $timeline = $this->checkElectionTimeline();
        if (!$timeline['allowed']) {
            return redirect()->route('election.portal')->with('error', $timeline['message']);
        }

        $request->validate([
            'no_kartu' => 'required|string',
            'otp' => 'required|string',
        ]);
        
        $nocard = str_pad($request->no_kartu, 4, '0', STR_PAD_LEFT);
        $member = Member::where('no_kartu', $nocard)->first();
        
        if (!$member) {
            return back()->withErrors(['no_kartu' => "Nomor kartu $nocard tidak valid atau tidak terdaftar."]);
        }

        $status = strtoupper($member->status_keanggotaan);
        if ($status !== 'LIFE MEMBER' && $status !== 'SS DIPONEGORO') {
            return back()->withErrors(['no_kartu' => 'Hanya anggota dengan status LIFE MEMBER atau SS DIPONEGORO yang dapat masuk.']);
        }

        // Check if already voted
        if (Polling::where('member_id', $member->id)->exists()) {
            return back()->withErrors(['no_kartu' => 'Anda sudah menyalurkan hak suara Anda dan tidak dapat masuk kembali.']);
        }

        // Verify OTP
        $otpRecord = Otp::where('member_id', $member->id)
            ->where('otp', $request->otp)
            ->where('is_verified', false)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (!$otpRecord) {
            return back()->withErrors(['otp' => 'Kode OTP tidak valid atau sudah kedaluwarsa.']);
        }
        
        $otpRecord->update(['is_verified' => true]);
        
        // Save election member ID to session
        $request->session()->put('election_member_id', $member->id);
        
        return redirect()->route('election.dashboard')->with([
            'success' => true,
            'message' => "Selamat datang kembali, {$member->nama_lengkap}!"
        ]);
    }

    /**
     * Handle logout.
     */
    public function logout(Request $request)
    {
        $request->session()->forget('election_member_id');
        return redirect()->route('election.login')->with([
            'success' => true,
            'message' => "Anda telah keluar dari portal."
        ]);
    }

    /**
     * Render the election dashboard for voting.
     */
    public function dashboard(Request $request)
    {
        $memberId = $request->session()->get('election_member_id');
        $existingVote = Polling::where('member_id', $memberId)->first();

        $candidates = Calon::with('member')->where('status', 'ditetapkan')->get();
        return Inertia::render('Election/dashboard', [
            'candidates' => $candidates,
            'hasVoted' => !is_null($existingVote),
            'votedCalonId' => $existingVote ? $existingVote->calon_id : null,
        ]);
    }

    public function vote(Request $request)
    {
        $request->validate([
            'calon_id' => 'required|exists:calons,id',
        ]);

        $memberId = $request->session()->get('election_member_id');

        $alreadyVoted = Polling::where('member_id', $memberId)->exists();
        if ($alreadyVoted) {
            return back()->withErrors(['vote' => 'Anda sudah menyalurkan suara Anda.']);
        }

        Polling::create([
            'member_id' => $memberId,
            'calon_id' => $request->calon_id,
        ]);

        // Auto logout: Clear the session!
        $request->session()->forget('election_member_id');

        // Redirect to live polling with a flash message!
        return redirect()->route('election.polling')->with([
            'success' => true,
            'message' => 'Pemberian suara Anda berhasil disimpan! Terima kasih atas partisipasi Anda.'
        ]);
    }

    public function livePolling()
    {
        $timeline = $this->checkPollingTimeline();
        if (!$timeline['allowed']) {
            return redirect()->route('election.portal')->with('error', $timeline['message']);
        }

        $pollingModel = new Polling();
        $results = $pollingModel->resultVotes();

        // Calculate percentages
        $totalVotes = array_sum(array_column($results, 'total_vote'));

        foreach ($results as &$res) {
            $res['percentage'] = $totalVotes > 0 ? round(($res['total_vote'] / $totalVotes) * 100, 1) : 0;
        }

        return Inertia::render('Election/polling', [
            'results' => $results,
            'totalVotes' => $totalVotes
        ]);
    }

    /**
     * Render the Pra-Election portal.
     */
    public function portal()
    {
        $settings = $this->getSettings();
        return Inertia::render('Election/portal', [
            'settings' => $settings
        ]);    
    }

    /**
     * Search members database by query for autocomplete.
     */
    public function searchMembers(Request $request)
    {
        $q = $request->query('q', '');
        if (strlen($q) < 2) {
            return response()->json([]);
        }
        
        $currentYear = intval(date('Y'));
        $cutoffYear = $currentYear - 10;

        $members = Member::where(function($query) use ($q) {
            $query->where('nama_lengkap', 'like', "%{$q}%")
                  ->orWhere('nama_panggilan', 'like', "%{$q}%")
                  ->orWhere('no_kartu', 'like', "%{$q}%")
                  ->orWhere('chapter', 'like', "%{$q}%");
        })
        ->whereRaw('UPPER(status_keanggotaan) = ?', ['LIFE MEMBER'])
        ->whereNotNull('terdaftar_sejak')
        ->where('terdaftar_sejak', '<=', $cutoffYear)
        ->take(15)->get();
        
        return response()->json($members);
    }

    /**
     * Fetch member details by KTA card number for autocomplete/validation.
     */
    public function getMemberInfo(Request $request, $nocard)
    {
        $role = $request->query('role', 'candidate');
        
        // Pad KTA to 4 digits (e.g. 23 -> 0023)
        $nocard = str_pad($nocard, 4, '0', STR_PAD_LEFT);
        $member = Member::where('no_kartu', $nocard)->first();
        
        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => "Anggota dengan No. Kartu $nocard tidak ditemukan di database."
            ]);
        }

        $status = strtoupper($member->status_keanggotaan);
        if ($status !== 'LIFE MEMBER') {
            return response()->json([
                'success' => false,
                'message' => "Hanya anggota dengan status LIFE MEMBER yang dapat berpartisipasi dalam pencalonan/pengajuan."
            ]);
        }

        $currentYear = intval(date('Y'));
        $memberYear = intval($member->terdaftar_sejak);
        if (empty($member->terdaftar_sejak) || ($currentYear - $memberYear) < 10) {
            return response()->json([
                'success' => false,
                'message' => "Hanya anggota dengan masa keanggotaan minimal 10 tahun yang dapat berpartisipasi dalam pencalonan/pengajuan."
            ]);
        }
        
        // Candidate KTA checks (candidates cannot be duplicated)
        if ($role === 'candidate') {
            $alreadyNominated = Calon::where('no_kartu', $nocard)->first();
            if ($alreadyNominated) {
                $statusText = $alreadyNominated->status === 'mengajukan' ? 'Pencalonan Mandiri' : 'Direkomendasikan Anggota';
                return response()->json([
                    'success' => false,
                    'message' => "Anggota dengan No. Kartu $nocard sudah terdaftar sebagai Calon El Presidente (Status: $statusText)."
                ]);
            }
        }
        
        // Note: For 'nominator' role, we ignore whether they are already nominated
        // because an active self-nominated candidate is allowed to nominate others!
        
        return response()->json([
            'success' => true,
            'member' => $member
        ]);
    }

    /**
     * Send OTP for nomination verification.
     */
    public function sendOtp(Request $request)
    {
        $request->validate([
            'no_kartu' => 'required|string',
        ]);

        $nocard = str_pad($request->no_kartu, 4, '0', STR_PAD_LEFT);
        $member = Member::where('no_kartu', $nocard)->first();

        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => "Anggota dengan No. Kartu $nocard tidak ditemukan di database."
            ], 404);
        }

        if (!$member->no_wa) {
            return response()->json([
                'success' => false,
                'message' => "Nomor WhatsApp anggota tidak ditemukan di database."
            ], 400);
        }

        $otpCode = (string) rand(100000, 999999);
        
        Otp::create([
            'member_id' => $member->id,
            'otp' => $otpCode,
            'phone' => $member->no_wa,
            'expires_at' => now()->addMinutes(5),
            'is_verified' => false,
        ]);

        $message = "*BBMC ELECTION 2026*\n\nKode OTP Anda untuk proses pengajuan pencalonan adalah: *$otpCode*\n\nBerlaku selama 5 menit. JANGAN BERIKAN KODE INI KEPADA SIAPAPUN.";
        
        \App\Helper::sendWhatsapp($member->no_wa, $message);

        return response()->json([
            'success' => true,
            'message' => 'OTP telah dikirim ke nomor WhatsApp Anda.'
        ]);
    }

    /**
     * Handle Self-Nomination ("Ajukan Diri Sebagai El Presidente")
     */
    public function nominateSelf(Request $request)
    {
        $settings = $this->getSettings();
        if (!$settings['ajukan_diri']) {
            return back()->withErrors(['otp' => 'Fitur pendaftaran mandiri (Ajukan Diri) saat ini dinonaktifkan oleh Administrator.']);
        }

        $request->validate([
            'no_kartu' => 'required|string',
            'otp' => 'required|string',
        ]);
        
        $nocard = str_pad($request->no_kartu, 4, '0', STR_PAD_LEFT);
        $member = Member::where('no_kartu', $nocard)->first();
        
        if (!$member) {
            return back()->withErrors(['no_kartu' => "Nomor kartu $nocard tidak valid atau tidak terdaftar."]);
        }

        $status = strtoupper($member->status_keanggotaan);
        if ($status !== 'LIFE MEMBER') {
            return back()->withErrors(['no_kartu' => 'Hanya anggota dengan status LIFE MEMBER yang dapat diajukan/mengajukan.']);
        }
        $currentYear = intval(date('Y'));
        $memberYear = intval($member->terdaftar_sejak);
        if (empty($member->terdaftar_sejak) || ($currentYear - $memberYear) < 10) {
            return back()->withErrors(['no_kartu' => 'Hanya anggota dengan masa keanggotaan minimal 10 tahun yang dapat diajukan/mengajukan.']);
        }

        // Verify OTP
        $otpRecord = Otp::where('member_id', $member->id)
            ->where('otp', $request->otp)
            ->where('is_verified', false)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (!$otpRecord) {
            return back()->withErrors(['otp' => 'Kode OTP tidak valid atau sudah kedaluwarsa.']);
        }
        
        $otpRecord->update(['is_verified' => true]);
        
        $alreadyNominated = Calon::where('no_kartu', $nocard)->exists();
        if ($alreadyNominated) {
            return back()->withErrors(['no_kartu' => 'Nomor kartu ini sudah terdaftar sebagai Calon El Presidente.']);
        }
        
        Calon::create([
            'member_id' => $member->id,
            'no_kartu' => $member->no_kartu,
            'chapter' => $member->chapter,
            'visi' => 'Pencalonan Mandiri',
            'misi' => 'Pencalonan Mandiri',
            'status' => 'mengajukan', // Self-nomination status
            'diajukan_oleh' => 'self',
            'no_kartu_diajukan_oleh' => null,
            'foto_calon' => $member->foto,
        ]);
        
        return back()->with([
            'success' => true,
            'message' => "Pencalonan diri Anda ({$member->nama_lengkap}) berhasil dikirim!"
        ]);
    }

    /**
     * Handle Member Endorsement ("Ajukan Anggota Sebagai El Presidente")
     */
    public function nominateMember(Request $request)
    {
        $settings = $this->getSettings();
        if (!$settings['ajukan_anggota']) {
            return back()->withErrors(['otp' => 'Fitur rekomendasi anggota (Ajukan Anggota) saat ini dinonaktifkan oleh Administrator.']);
        }

        $request->validate([
            'candidate_name' => 'required|string',
            'nominator_no_kartu' => 'required|string',
            'otp' => 'required|string',
        ]);
        
        // Find nominator by card number
        $nominatorNocard = str_pad($request->nominator_no_kartu, 4, '0', STR_PAD_LEFT);
        $nominator = Member::where('no_kartu', $nominatorNocard)->first();
        
        if (!$nominator) {
            return back()->withErrors(['nominator_no_kartu' => "Nomor kartu pengusul ($nominatorNocard) tidak valid atau tidak terdaftar."]);
        }

        $nominatorStatus = strtoupper($nominator->status_keanggotaan);
        if ($nominatorStatus !== 'LIFE MEMBER') {
            return back()->withErrors(['nominator_no_kartu' => 'Hanya anggota dengan status LIFE MEMBER yang dapat mengajukan/merekomendasikan.']);
        }
        $currentYear = intval(date('Y'));
        $nominatorYear = intval($nominator->terdaftar_sejak);
        if (empty($nominator->terdaftar_sejak) || ($currentYear - $nominatorYear) < 10) {
            return back()->withErrors(['nominator_no_kartu' => 'Hanya anggota dengan masa keanggotaan minimal 10 tahun yang dapat mengajukan/merekomendasikan.']);
        }

        // Verify OTP
        $otpRecord = Otp::where('member_id', $nominator->id)
            ->where('otp', $request->otp)
            ->where('is_verified', false)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (!$otpRecord) {
            return back()->withErrors(['otp' => 'Kode OTP tidak valid atau sudah kedaluwarsa.']);
        }
        
        $otpRecord->update(['is_verified' => true]);

        // Find candidate by name
        $candidateName = $request->candidate_name;
        $candidate = Member::where('nama_lengkap', $candidateName)
            ->orWhere('nama_panggilan', $candidateName)
            ->first();
            
        // Fallback robust search if exact match fails
        if (!$candidate) {
            $candidate = Member::where('nama_lengkap', 'like', "%{$candidateName}%")
                ->orWhere('nama_panggilan', 'like', "%{$candidateName}%")
                ->first();
        }
            
        if (!$candidate) {
            return back()->withErrors(['candidate_name' => "Nama anggota yang dicalonkan ('$candidateName') tidak ditemukan dalam database."]);
        }

        $candidateStatus = strtoupper($candidate->status_keanggotaan);
        if ($candidateStatus !== 'LIFE MEMBER') {
            return back()->withErrors(['candidate_name' => 'Hanya anggota dengan status LIFE MEMBER yang dapat diajukan sebagai calon.']);
        }
        $candidateYear = intval($candidate->terdaftar_sejak);
        if (empty($candidate->terdaftar_sejak) || ($currentYear - $candidateYear) < 10) {
            return back()->withErrors(['candidate_name' => 'Hanya anggota dengan masa keanggotaan minimal 10 tahun yang dapat diajukan sebagai calon.']);
        }
        
        // Check if candidate is already registered
        $alreadyNominated = Calon::where('no_kartu', $candidate->no_kartu)->exists();
        if ($alreadyNominated) {
            return back()->withErrors(['candidate_name' => "Anggota '{$candidate->nama_lengkap}' sudah terdaftar sebagai Calon El Presidente."]);
        }
        
        Calon::create([
            'member_id' => $candidate->id,
            'no_kartu' => $candidate->no_kartu,
            'chapter' => $candidate->chapter,
            'visi' => "Rekomendasi pencalonan oleh: {$nominator->nama_lengkap}",
            'misi' => null,
            'status' => 'diajukan', // Peer-nomination status
            'diajukan_oleh' => $nominator->nama_lengkap,
            'no_kartu_diajukan_oleh' => $nominator->no_kartu,
            'foto_calon' => $candidate->foto,
        ]);
        
        return back()->with([
            'success' => true,
            'message' => "Rekomendasi pencalonan untuk {$candidate->nama_lengkap} berhasil dikirim!"
        ]);
    }

    public function polling()
    {
        $timeline = $this->checkPollingTimeline();
        if (!$timeline['allowed']) {
            return redirect()->route('election.portal')->with('error', $timeline['message']);
        }

        $data = (new Polling())->resultVotes();
        return Inertia::render('Election/polling',[
            'results' => $data
        ]);
    }

    /**
     * Render the election settings page (Dashboard).
     */
    public function setting()
    {
        $settings = $this->getSettings();
        return Inertia::render('Election/Setting', [
            'settings' => $settings
        ]);
    }

    /**
     * Handle saving election settings from dashboard.
     */
    public function settingPost(Request $request)
    {
        $validated = $request->validate([
            'ajukan_diri' => 'required|boolean',
            'ajukan_anggota' => 'required|boolean',
            'tanggal_mulai' => 'nullable|string',
            'tanggal_selesai' => 'nullable|string',
            'piwapi_api_secret_key' => 'nullable|string',
            'piwapi_account_id' => 'nullable|string',
        ]);

        $validated['ajukan_diri'] = (bool)$validated['ajukan_diri'];
        $validated['ajukan_anggota'] = (bool)$validated['ajukan_anggota'];

        $path = storage_path('app/private/pemilihan-setting.json');
        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($path, json_encode($validated, JSON_PRETTY_PRINT));

        return back()->with('success', 'Pengaturan pemilihan berhasil diperbarui.');
    }
}
