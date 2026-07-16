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

        if ($member->penalty && $member->penalty !== 'clean') {
            $reasonMsg = $member->penalty_reason ? " Alasan: {$member->penalty_reason}" : "";
            return response()->json([
                'success' => false,
                'penalty' => true,
                'penalty_status' => strtoupper($member->penalty),
                'penalty_reason' => $member->penalty_reason,
                'message' => "Anda tidak dapat masuk karena status keanggotaan sedang dalam masa penalty (" . strtoupper($member->penalty) . "). Harus berstatus CLEAN / NO PENALTY.{$reasonMsg}"
            ], 403);
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

        // Cooldown request ulang setiap 1 menit
        $recentOtp = Otp::where('member_id', $member->id)
            ->where('is_verified', false)
            ->where('created_at', '>', now()->subMinute())
            ->first();

        if ($recentOtp) {
            return response()->json([
                'success' => false,
                'message' => 'Anda baru saja meminta kode OTP. Mohon tunggu 1 menit sebelum meminta kembali.'
            ], 429);
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

        if ($member->penalty && $member->penalty !== 'clean') {
            $reasonMsg = $member->penalty_reason ? " Alasan: {$member->penalty_reason}" : "";
            return back()->withErrors(['no_kartu' => "Anda tidak dapat masuk karena status keanggotaan sedang dalam masa penalty (" . strtoupper($member->penalty) . "). Harus berstatus CLEAN / NO PENALTY.{$reasonMsg}"]);
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

        $candidates = Calon::with('member')->where('status', 'ditetapkan')->get()->unique('member_id')->values();
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
                  ->orWhere('chapter', 'like', "%{$q}%")
                  ->orWhere('checkpoint', 'like', "%{$q}%");
        })
        ->where(function($sq) {
            $sq->whereRaw('UPPER(status_keanggotaan) = ?', ['LIFE MEMBER'])
               ->orWhereRaw('UPPER(status_keanggotaan) = ?', ['SS DIPONEGORO']);
        })
        ->where(function($pq) {
            $pq->whereNull('penalty')
               ->orWhere('penalty', '')
               ->orWhere('penalty', 'clean');
        })
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

        if ($member->penalty && $member->penalty !== 'clean') {
            $reasonMsg = $member->penalty_reason ? " Alasan: {$member->penalty_reason}" : "";
            return response()->json([
                'success' => false,
                'message' => "Anggota dengan No. Kartu {$member->no_kartu} sedang dalam masa penalty (" . strtoupper($member->penalty) . "). Harus berstatus CLEAN / NO PENALTY.{$reasonMsg}"
            ]);
        }

        if ($role === 'candidate') {
            $status = strtoupper($member->status_keanggotaan);
            if ($status !== 'LIFE MEMBER' && $status !== 'SS DIPONEGORO') {
                return response()->json([
                    'success' => false,
                    'message' => "Hanya anggota dengan status LIFE MEMBER atau SS DIPONEGORO yang dapat berpartisipasi dalam pencalonan/pengajuan."
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

            $alreadyNominatedSelf = Calon::where('no_kartu', $nocard)->where('diajukan_oleh', 'self')->first();
            if ($alreadyNominatedSelf) {
                return response()->json([
                    'success' => false,
                    'message' => "Anggota dengan No. Kartu $nocard sudah melakukan Pencalonan Mandiri sebelumnya."
                ]);
            }
        }
        
        // Note: For 'nominator' role, we allow all status members to nominate and do not check 10-year restriction.
        // Penalty is already checked above.
        
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

        if ($member->penalty && $member->penalty !== 'clean') {
            $reasonMsg = $member->penalty_reason ? " Alasan: {$member->penalty_reason}" : "";
            return response()->json([
                'success' => false,
                'message' => "Anda tidak dapat melanjutkan karena status keanggotaan sedang dalam masa penalty (" . strtoupper($member->penalty) . "). Harus berstatus CLEAN / NO PENALTY.{$reasonMsg}"
            ], 403);
        }

        if ($request->type === 'self') {
            $status = strtoupper($member->status_keanggotaan);
            if ($status !== 'LIFE MEMBER' && $status !== 'SS DIPONEGORO') {
                return response()->json([
                    'success' => false,
                    'message' => 'Hanya anggota dengan status LIFE MEMBER atau SS DIPONEGORO yang dapat mengajukan diri.'
                ], 403);
            }
            $currentYear = intval(date('Y'));
            $memberYear = intval($member->terdaftar_sejak);
            if (empty($member->terdaftar_sejak) || ($currentYear - $memberYear) < 10) {
                return response()->json([
                    'success' => false,
                    'message' => 'Hanya anggota dengan masa keanggotaan minimal 10 tahun yang dapat mengajukan diri.'
                ], 403);
            }
        }

        if (!$member->no_wa) {
            return response()->json([
                'success' => false,
                'message' => "Nomor WhatsApp anggota tidak ditemukan di database."
            ], 400);
        }

        // Cooldown request ulang setiap 1 menit
        $recentOtp = Otp::where('member_id', $member->id)
            ->where('is_verified', false)
            ->where('created_at', '>', now()->subMinute())
            ->first();

        if ($recentOtp) {
            return response()->json([
                'success' => false,
                'message' => 'Anda baru saja meminta kode OTP. Mohon tunggu 1 menit sebelum meminta kembali.'
            ], 429);
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

        if ($member->penalty && $member->penalty !== 'clean') {
            $reasonMsg = $member->penalty_reason ? " Alasan: {$member->penalty_reason}" : "";
            return back()->withErrors(['no_kartu' => "Anda tidak dapat mengajukan diri karena status keanggotaan sedang dalam masa penalty (" . strtoupper($member->penalty) . "). Harus berstatus CLEAN / NO PENALTY.{$reasonMsg}"]);
        }

        $status = strtoupper($member->status_keanggotaan);
        if ($status !== 'LIFE MEMBER' && $status !== 'SS DIPONEGORO') {
            return back()->withErrors(['no_kartu' => 'Hanya anggota dengan status LIFE MEMBER atau SS DIPONEGORO yang dapat mengajukan diri.']);
        }
        $currentYear = intval(date('Y'));
        $memberYear = intval($member->terdaftar_sejak);
        if (empty($member->terdaftar_sejak) || ($currentYear - $memberYear) < 10) {
            return back()->withErrors(['no_kartu' => 'Hanya anggota dengan masa keanggotaan minimal 10 tahun yang dapat mengajukan diri.']);
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
        
        $alreadyNominatedSelf = Calon::where('no_kartu', $nocard)->where('diajukan_oleh', 'self')->exists();
        if ($alreadyNominatedSelf) {
            return back()->withErrors(['no_kartu' => 'Anda sudah melakukan Pencalonan Mandiri sebelumnya.']);
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

        if ($nominator->penalty && $nominator->penalty !== 'clean') {
            $reasonMsg = $nominator->penalty_reason ? " Alasan: {$nominator->penalty_reason}" : "";
            return back()->withErrors(['nominator_no_kartu' => "Anda tidak dapat merekomendasikan karena status keanggotaan Anda sedang dalam masa penalty (" . strtoupper($nominator->penalty) . "). Harus berstatus CLEAN / NO PENALTY.{$reasonMsg}"]);
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

        // Find candidate by card number / id first if passed, fallback to candidate_name
        $candidate = null;
        if ($request->filled('candidate_no_kartu')) {
            $candidate = Member::where('no_kartu', str_pad($request->candidate_no_kartu, 4, '0', STR_PAD_LEFT))->first();
        }
        if (!$candidate && $request->filled('candidate_id')) {
            $candidate = Member::find($request->candidate_id);
        }
        if (!$candidate) {
            $candidateName = $request->candidate_name;
            $candidate = Member::where('nama_lengkap', $candidateName)
                ->orWhere('nama_panggilan', $candidateName)
                ->first();
                
            if (!$candidate) {
                $candidate = Member::where('nama_lengkap', 'like', "%{$candidateName}%")
                    ->orWhere('nama_panggilan', 'like', "%{$candidateName}%")
                    ->first();
            }
        }
            
        if (!$candidate) {
            return back()->withErrors(['candidate_name' => "Nama anggota yang dicalonkan tidak ditemukan dalam database."]);
        }

        if ($candidate->penalty && $candidate->penalty !== 'clean') {
            $reasonMsg = $candidate->penalty_reason ? " Alasan: {$candidate->penalty_reason}" : "";
            return back()->withErrors(['candidate_name' => "Anggota yang diajukan ('{$candidate->nama_lengkap}') tidak dapat dicalonkan karena sedang dalam masa penalty (" . strtoupper($candidate->penalty) . "). Harus berstatus CLEAN / NO PENALTY.{$reasonMsg}"]);
        }

        $candidateStatus = strtoupper($candidate->status_keanggotaan);
        if ($candidateStatus !== 'LIFE MEMBER' && $candidateStatus !== 'SS DIPONEGORO') {
            return back()->withErrors(['candidate_name' => 'Hanya anggota dengan status LIFE MEMBER atau SS DIPONEGORO yang dapat diajukan sebagai calon.']);
        }
        $currentYear = intval(date('Y'));
        $candidateYear = intval($candidate->terdaftar_sejak);
        if (empty($candidate->terdaftar_sejak) || ($currentYear - $candidateYear) < 10) {
            return back()->withErrors(['candidate_name' => 'Hanya anggota dengan masa keanggotaan minimal 10 tahun yang dapat diajukan sebagai calon.']);
        }
        
        // Check if nominator already recommended this exact candidate
        $alreadyNominatedByThisMember = Calon::where('member_id', $candidate->id)
            ->where('no_kartu_diajukan_oleh', $nominator->no_kartu)
            ->exists();
        if ($alreadyNominatedByThisMember) {
            return back()->withErrors(['candidate_name' => "Anda ('{$nominator->nama_lengkap}') sudah merekomendasikan '{$candidate->nama_lengkap}' sebagai Calon El Presidente sebelumnya."]);
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
