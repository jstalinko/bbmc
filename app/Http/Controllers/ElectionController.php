<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Calon;
use App\Models\Otp;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ElectionController extends Controller
{
    /**
     * Render the secure election login page.
     */
    public function login()
    {
        return Inertia::render('Election/login');
    }

    /**
     * Render the Pra-Election portal.
     */
    public function portal()
    {
        return Inertia::render('Election/portal');    
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
        
        $members = Member::where(function($query) use ($q) {
            $query->where('nama_lengkap', 'like', "%{$q}%")
                  ->orWhere('nama_panggilan', 'like', "%{$q}%")
                  ->orWhere('no_kartu', 'like', "%{$q}%");
        })->take(5)->get();
        
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
        
        // Helper::sendWhatsapp
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
        $request->validate([
            'no_kartu' => 'required|string',
            'otp' => 'required|string',
        ]);
        
        $nocard = str_pad($request->no_kartu, 4, '0', STR_PAD_LEFT);
        $member = Member::where('no_kartu', $nocard)->first();
        
        if (!$member) {
            return back()->withErrors(['no_kartu' => "Nomor kartu $nocard tidak valid atau tidak terdaftar."]);
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
        $request->validate([
            'candidate_name' => 'required|string',
            'nominator_no_kartu' => 'required|string',
            'alasan' => 'required|string',
            'otp' => 'required|string',
        ]);
        
        // Find nominator by card number
        $nominatorNocard = str_pad($request->nominator_no_kartu, 4, '0', STR_PAD_LEFT);
        $nominator = Member::where('no_kartu', $nominatorNocard)->first();
        
        if (!$nominator) {
            return back()->withErrors(['nominator_no_kartu' => "Nomor kartu pengusul ($nominatorNocard) tidak valid atau tidak terdaftar."]);
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
            'misi' => $request->alasan,
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
}
