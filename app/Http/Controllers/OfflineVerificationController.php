<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\OfflineLog;
use App\Models\Polling;
use App\Models\User;
use App\Models\ElectionQueue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class OfflineVerificationController extends Controller
{
    /**
     * Display the offline verification page and queue list.
     */
    public function index(Request $request)
    {
        $pengurusKode = $request->session()->get('offline_pengurus_kode');
        $pengurusNama = $request->session()->get('offline_pengurus_nama');
        $currentPengurus = null;

        $settings = ElectionController::getRawSettings();
        $configuredPengurus = $settings['kode_akses_pengurus'] ?? [];

        // Verify pengurus session against configured access codes
        if ($pengurusKode) {
            $found = null;
            foreach ($configuredPengurus as $p) {
                if (strcasecmp(trim($p['kode_akses'] ?? ''), trim($pengurusKode)) === 0) {
                    $found = $p;
                    break;
                }
            }

            if ($found) {
                $currentPengurus = [
                    'kode_akses' => $found['kode_akses'],
                    'nama_pengurus' => $found['nama_pengurus'] ?: $pengurusNama,
                ];
            } else {
                $request->session()->forget(['offline_pengurus_kode', 'offline_pengurus_nama']);
            }
        }

        // If pengurus is authenticated, load the offline queues and stats
        $queueList = null;
        $queueStats = null;

        if ($currentPengurus) {
            $query = OfflineLog::with(['member' => function ($q) {
                $q->select('id', 'nama_lengkap', 'nama_panggilan', 'no_kartu', 'status_keanggotaan', 'chapter', 'foto');
            }])->orderBy('no_antrian', 'asc');

            if ($request->filled('search')) {
                $s = trim($request->search);
                $padded = str_pad($s, 4, '0', STR_PAD_LEFT);
                $query->where(function ($q) use ($s, $padded) {
                    $q->where('no_antrian', $s)
                      ->orWhere('nama_pengurus', 'like', "%{$s}%")
                      ->orWhere('no_tps', 'like', "%{$s}%")
                      ->orWhereHas('member', function ($mq) use ($s, $padded) {
                          $mq->where('nama_lengkap', 'like', "%{$s}%")
                             ->orWhere('nama_panggilan', 'like', "%{$s}%")
                             ->orWhere('no_kartu', 'like', "%{$s}%")
                             ->orWhere('no_kartu', $padded);
                      });
                });
            }

            if ($request->filled('status') && $request->status !== 'all') {
                $query->where('voting_status', $request->status);
            }

            $queueList = $query->paginate(25)->withQueryString();

            $queueStats = [
                'total_antrian' => OfflineLog::where('voting_status', OfflineLog::STATUS_ANTREAN)->count(),
                'total_sudah_memilih' => OfflineLog::where('voting_status', OfflineLog::STATUS_SUDAH_MEMILIH)->count(),
                'total_tidak_memilih' => OfflineLog::where('voting_status', OfflineLog::STATUS_TIDAK_MEMILIH)->count(),
                'total_batal' => OfflineLog::where('voting_status', OfflineLog::STATUS_CANCEL_VOTE)->count(),
                'total_semua' => OfflineLog::count(),
            ];
        }

        return Inertia::render('Offline/Verify', [
            'current_pengurus' => $currentPengurus,
            'queue_list' => $queueList,
            'queue_stats' => $queueStats,
            'next_queue_suggestion' => (OfflineLog::max('no_antrian') ?? 0) + 1,
            'filters' => [
                'search' => $request->input('search', ''),
                'status' => $request->input('status', 'all'),
            ],
            'status_labels' => OfflineLog::STATUS_LABELS,
        ]);
    }

    /**
     * Authenticate pengurus using access code.
     */
    public function login(Request $request)
    {
        $request->validate([
            'kode_akses' => 'required|string',
        ], [
            'kode_akses.required' => 'Kode akses pengurus wajib diisi.',
        ]);

        $settings = ElectionController::getRawSettings();
        $configuredPengurus = $settings['kode_akses_pengurus'] ?? [];

        if (empty($configuredPengurus)) {
            return back()->withErrors([
                'kode_akses' => 'Belum ada kode akses pengurus yang diatur. Silakan atur terlebih dahulu di Dashboard > Setting Pemilihan.'
            ]);
        }

        $inputCode = trim($request->kode_akses);
        $found = null;

        foreach ($configuredPengurus as $p) {
            if (strcasecmp(trim($p['kode_akses'] ?? ''), $inputCode) === 0) {
                $found = $p;
                break;
            }
        }

        if (!$found) {
            return back()->withErrors([
                'kode_akses' => 'Kode akses pengurus tidak valid atau belum terdaftar.'
            ]);
        }

        $request->session()->put('offline_pengurus_kode', $found['kode_akses']);
        $request->session()->put('offline_pengurus_nama', $found['nama_pengurus'] ?: 'Pengurus Pemilihan');

        return redirect()->route('offline.verify')->with('success', 'Selamat bertugas, ' . ($found['nama_pengurus'] ?: 'Pengurus') . '!');
    }

    /**
     * Logout pengurus session.
     */
    public function logout(Request $request)
    {
        $request->session()->forget(['offline_pengurus_kode', 'offline_pengurus_nama']);
        return redirect()->route('offline.verify')->with('info', 'Anda telah keluar dari sesi pengurus.');
    }

    /**
     * Verify member card and register into offline logs queue.
     */
    public function verifyMember(Request $request)
    {
        $pengurusKode = $request->session()->get('offline_pengurus_kode');
        $pengurusNama = $request->session()->get('offline_pengurus_nama');

        if (!$pengurusKode) {
            return redirect()->route('offline.verify')->withErrors([
                'no_kartu' => 'Sesi pengurus telah berakhir. Silakan masukkan kode akses pengurus kembali.'
            ]);
        }

        $request->validate([
            'no_kartu'   => 'required|digits_between:1,4',
            'no_antrian' => 'required|integer|min:1',
            'no_tps'     => 'nullable|string|max:50',
        ], [
            'no_kartu.required'      => 'Nomor kartu anggota wajib diisi.',
            'no_kartu.digits_between'=> 'Nomor kartu harus berupa angka maksimal 4 digit.',
            'no_antrian.required'    => 'Nomor antrean wajib diisi secara manual.',
            'no_antrian.integer'     => 'Nomor antrean harus berupa angka.',
            'no_antrian.min'         => 'Nomor antrean minimal 1.',
        ]);

        $nocard = str_pad($request->no_kartu, 4, '0', STR_PAD_LEFT);
        $member = Member::where('no_kartu', $nocard)->first();

        // Check if queue number is already taken
        $existingQueue = OfflineLog::where('no_antrian', $request->no_antrian)->with('member')->first();
        if ($existingQueue) {
            $takenMember = $existingQueue->member ? $existingQueue->member->nama_lengkap : "Member #{$existingQueue->member_id}";
            return back()->withErrors([
                'no_antrian' => "Nomor antrean #{$request->no_antrian} sudah digunakan oleh {$takenMember} (KTA: " . ($existingQueue->member?->no_kartu ?? '—') . "). Silakan gunakan nomor antrean lain."
            ]);
        }

        // 1. Check member existence
        if (!$member) {
            return back()->withErrors([
                'no_kartu' => "Nomor kartu $nocard tidak terdaftar sebagai anggota BBMC."
            ]);
        }

        // 2. Check voting eligibility (status keanggotaan)
        $status = strtoupper($member->status_keanggotaan);
        if ($status !== 'LIFE MEMBER' && $status !== 'SS DIPONEGORO') {
            return back()->withErrors([
                'no_kartu' => "Anggota {$member->nama_lengkap} (KTA: $nocard) tidak berhak memilih karena status keanggotaan adalah '{$member->status_keanggotaan}'. Hanya status LIFE MEMBER atau SS DIPONEGORO yang berhak memilih."
            ]);
        }

        // 3. Check penalty status
        if ($member->penalty && $member->penalty !== 'clean') {
            $reason = $member->penalty_reason ? " (Alasan: {$member->penalty_reason})" : "";
            return back()->withErrors([
                'no_kartu' => "Anggota {$member->nama_lengkap} (KTA: $nocard) tidak dapat diverifikasi karena sedang dalam masa penalty '" . strtoupper($member->penalty) . "'{$reason}. Wajib berstatus CLEAN."
            ]);
        }

        // 4. Condition: Has the member already voted online?
        if (Polling::where('member_id', $member->id)->exists()) {
            return back()->withErrors([
                'no_kartu' => "Anggota {$member->nama_lengkap} (KTA: $nocard) sudah memberikan hak suaranya secara online dan tidak dapat didaftarkan sebagai pemilih offline."
            ]);
        }

        // 5. Condition: Is the member already marked in offline_logs?
        $existingLog = OfflineLog::where('member_id', $member->id)->first();
        if ($existingLog) {
            if ($existingLog->voting_status === OfflineLog::STATUS_ANTREAN) {
                return back()->withErrors([
                    'no_kartu' => "Anggota {$member->nama_lengkap} (KTA: $nocard) sudah terdaftar dalam antrean pemilihan offline (No. Antrean: #{$existingLog->no_antrian}) yang diverifikasi oleh pengurus '{$existingLog->nama_pengurus}'."
                ]);
            }

            if ($existingLog->voting_status === OfflineLog::STATUS_SUDAH_MEMILIH) {
                return back()->withErrors([
                    'no_kartu' => "Anggota {$member->nama_lengkap} (KTA: $nocard) sudah menggunakan hak suaranya dalam pemilihan offline (No. Antrean: #{$existingLog->no_antrian}) yang diverifikasi oleh pengurus '{$existingLog->nama_pengurus}'."
                ]);
            }

            if ($existingLog->voting_status === OfflineLog::STATUS_TIDAK_MEMILIH) {
                return back()->withErrors([
                    'no_kartu' => "Anggota {$member->nama_lengkap} (KTA: $nocard) sudah tercatat dalam log pemilihan offline dengan status 'Tidak Memilih' (No. Antrean: #{$existingLog->no_antrian})."
                ]);
            }

            return back()->withErrors([
                'no_kartu' => "Anggota {$member->nama_lengkap} (KTA: $nocard) sudah tercatat dalam log pemilihan offline dengan status '{$existingLog->status_label}' (No. Antrean: #{$existingLog->no_antrian})."
            ]);
        }

        // 6. Condition: Already marked as offline_voter in members table
        if ($member->offline_voter) {
            return back()->withErrors([
                'no_kartu' => "Anggota {$member->nama_lengkap} (KTA: $nocard) sudah ditandai sebagai pemilih offline di sistem."
            ]);
        }

        // If all conditions pass: register into offline_logs and mark offline_voter
        DB::beginTransaction();
        try {
            $manualQueue = (int) $request->no_antrian;

            $offlineLog = OfflineLog::create([
                'nama_pengurus' => $pengurusNama,
                'kode_akses'   => $pengurusKode,
                'member_id'     => $member->id,
                'no_antrian'    => $manualQueue,
                'no_tps'        => $request->no_tps ?: null,
                'voting_status' => OfflineLog::STATUS_ANTREAN,
            ]);

            $member->offline_voter = true;
            $member->save();

            if (!empty($member->email)) {
                User::where('email', $member->email)->update(['offline_voter' => true]);
            }

            ElectionQueue::where('member_id', $member->id)->delete();

            DB::commit();

            return back()->with([
                'success' => "Anggota {$member->nama_lengkap} (KTA: $nocard) berhasil diverifikasi dan masuk antrean offline!",
                'verified_success' => [
                    'no_antrian' => $manualQueue,
                    'nama_lengkap' => $member->nama_lengkap,
                    'no_kartu' => $member->no_kartu,
                    'status_keanggotaan' => $member->status_keanggotaan,
                    'chapter' => $member->chapter,
                    'no_tps' => $request->no_tps ?: '—',
                    'nama_pengurus' => $pengurusNama,
                ]
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors([
                'no_kartu' => 'Terjadi kesalahan sistem saat mendaftarkan pemilih offline: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Update voting status in offline log (e.g. sudah_memilih, tidak_memilih, cancel_vote, antrean).
     */
    public function updateStatus(Request $request, OfflineLog $offlineLog)
    {
        $pengurusKode = $request->session()->get('offline_pengurus_kode');
        if (!$pengurusKode) {
            return back()->withErrors(['error' => 'Sesi pengurus telah berakhir. Silakan login kembali.']);
        }

        $request->validate([
            'voting_status' => 'required|in:' . implode(',', OfflineLog::STATUSES),
        ], [
            'voting_status.required' => 'Status pemilihan wajib dipilih.',
            'voting_status.in' => 'Status pemilihan tidak valid.',
        ]);

        $oldStatus = $offlineLog->voting_status;
        $newStatus = $request->voting_status;

        $offlineLog->voting_status = $newStatus;
        $offlineLog->save();

        // If cancelled, should offline_voter be toggled or kept?
        // If cancelled, keep member as offline_voter or toggle: let's toggle offline_voter false if cancel_vote
        // so that if accidentally verified, cancelling frees the member, or if changed back to antrean/voted, restores offline_voter.
        if ($newStatus === OfflineLog::STATUS_CANCEL_VOTE) {
            $offlineLog->member->update(['offline_voter' => false]);
            if (!empty($offlineLog->member->email)) {
                User::where('email', $offlineLog->member->email)->update(['offline_voter' => false]);
            }
        } elseif (in_array($newStatus, [OfflineLog::STATUS_ANTREAN, OfflineLog::STATUS_SUDAH_MEMILIH, OfflineLog::STATUS_TIDAK_MEMILIH])) {
            $offlineLog->member->update(['offline_voter' => true]);
            if (!empty($offlineLog->member->email)) {
                User::where('email', $offlineLog->member->email)->update(['offline_voter' => true]);
            }
        }

        $label = OfflineLog::STATUS_LABELS[$newStatus] ?? $newStatus;
        $memberName = $offlineLog->member ? $offlineLog->member->nama_lengkap : "Member #{$offlineLog->member_id}";

        return back()->with('success', "Status antrean #{$offlineLog->no_antrian} ({$memberName}) berhasil diubah menjadi: '{$label}'.");
    }
}
