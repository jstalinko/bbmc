<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\OfflineLog;
use App\Models\Polling;
use App\Models\PollingOffline;
use App\Models\Calon;
use App\Models\User;
use App\Models\ElectionQueue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class OfflineElectionController extends Controller
{
    /**
     * Display a listing of offline election logs from offline_logs and member.
     */
    public function index(Request $request)
    {
        $query = OfflineLog::with(['member' => function ($q) {
            $q->withExists('pollings');
        }]);

        // Search by Nama Lengkap, Nama Panggilan, No. Kartu (KTA), No. Antrean, Petugas, or TPS
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $paddedNocard = str_pad($search, 4, '0', STR_PAD_LEFT);
            $query->where(function ($q) use ($search, $paddedNocard) {
                $q->where('no_antrian', $search)
                  ->orWhere('nama_pengurus', 'like', "%{$search}%")
                  ->orWhere('no_tps', 'like', "%{$search}%")
                  ->orWhereHas('member', function ($mq) use ($search, $paddedNocard) {
                      $mq->where('nama_lengkap', 'like', "%{$search}%")
                         ->orWhere('nama_panggilan', 'like', "%{$search}%")
                         ->orWhere('no_kartu', 'like', "%{$search}%")
                         ->orWhere('no_kartu', $paddedNocard)
                         ->orWhere('no_wa', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by voting status (antrean, sudah_memilih, tidak_memilih, cancel_vote)
        if ($request->filled('voting_status') && $request->input('voting_status') !== 'all') {
            $query->where('voting_status', $request->input('voting_status'));
        }

        // Filter by membership status
        if ($request->filled('status_keanggotaan') && $request->input('status_keanggotaan') !== 'all') {
            $status = $request->input('status_keanggotaan');
            $query->whereHas('member', function ($q) use ($status) {
                $q->where('status_keanggotaan', $status);
            });
        }

        // Sorting
        $allowedSorts = ['no_antrian', 'voting_status', 'no_tps', 'created_at', 'nama_pengurus'];
        $sortBy = $request->input('sort_by');
        $sortDir = strtolower($request->input('sort_dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        if ($sortBy && in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir)->orderBy('id', 'asc');
        } else {
            // Default: ordered ascending by queue number
            $query->orderBy('no_antrian', 'asc');
        }

        $logs = $query->paginate(15)->withQueryString();

        // Calculate statistics for offline logs & offline vote recap
        $stats = [
            'total_offline_voters' => OfflineLog::count(),
            'total_antrean'        => OfflineLog::where('voting_status', OfflineLog::STATUS_ANTREAN)->count(),
            'total_sudah_memilih'  => OfflineLog::where('voting_status', OfflineLog::STATUS_SUDAH_MEMILIH)->count(),
            'total_tidak_memilih'  => OfflineLog::where('voting_status', OfflineLog::STATUS_TIDAK_MEMILIH)->count(),
            'total_batal'          => OfflineLog::where('voting_status', OfflineLog::STATUS_CANCEL_VOTE)->count(),
            'total_online_voted'   => Polling::distinct('member_id')->count('member_id'),
            'total_rekap_offline'  => (int) PollingOffline::sum('jumlah_suara'),
            'total_rekap_count'    => PollingOffline::count(),
        ];

        $rekapOffline = PollingOffline::with(['calon.member', 'creator'])
            ->orderBy('id', 'desc')
            ->get();

        $candidates = Calon::with('member')
            ->where('status', 'ditetapkan')
            ->orderByRaw('CASE WHEN no_urut IS NULL OR no_urut = 0 THEN 1 ELSE 0 END, no_urut ASC, id ASC')
            ->get()
            ->unique('member_id')
            ->values()
            ->map(function ($c) {
                return [
                    'id' => $c->id,
                    'no_urut' => $c->no_urut,
                    'nama' => $c->member ? $c->member->nama_lengkap : 'Calon #' . $c->id,
                    'chapter' => $c->chapter ?? ($c->member ? $c->member->chapter : ''),
                ];
            });

        return Inertia::render('Election/Offline', [
            'logs' => $logs,
            'stats' => $stats,
            'rekap_offline' => $rekapOffline,
            'candidates' => $candidates,
            'filters' => [
                'search' => $request->input('search', ''),
                'voting_status' => $request->input('voting_status', 'all'),
                'status_keanggotaan' => $request->input('status_keanggotaan', 'all'),
                'sort_by' => $request->input('sort_by', ''),
                'sort_dir' => $request->input('sort_dir', ''),
            ],
            'status_labels' => OfflineLog::STATUS_LABELS,
        ]);
    }

    /**
     * Update an offline election log entry.
     */
    public function update(Request $request, OfflineLog $offlineLog)
    {
        $validated = $request->validate([
            'no_antrian'    => 'required|integer|min:1',
            'no_tps'        => 'nullable|string|max:50',
            'nama_pengurus' => 'required|string|max:255',
            'voting_status' => 'required|in:' . implode(',', OfflineLog::STATUSES),
        ], [
            'no_antrian.required'    => 'Nomor antrean wajib diisi.',
            'no_antrian.integer'     => 'Nomor antrean harus berupa angka.',
            'nama_pengurus.required' => 'Nama pengurus wajib diisi.',
            'voting_status.required' => 'Status pemilihan wajib dipilih.',
            'voting_status.in'       => 'Status pemilihan tidak valid.',
        ]);

        $offlineLog->update($validated);

        // Sync member offline_voter status
        $member = $offlineLog->member;
        if ($member) {
            if ($validated['voting_status'] === OfflineLog::STATUS_CANCEL_VOTE) {
                $member->update(['offline_voter' => false]);
                if (!empty($member->email)) {
                    User::where('email', $member->email)->update(['offline_voter' => false]);
                }
            } else {
                $member->update(['offline_voter' => true]);
                if (!empty($member->email)) {
                    User::where('email', $member->email)->update(['offline_voter' => true]);
                }
            }
        }

        return back()->with('success', "Data antrean #{$offlineLog->no_antrian} berhasil diperbarui.");
    }

    /**
     * Delete an offline election log entry.
     */
    public function destroy(OfflineLog $offlineLog)
    {
        $member = $offlineLog->member;
        $noAntrian = $offlineLog->no_antrian;
        $memberName = $member ? $member->nama_lengkap : "Member #{$offlineLog->member_id}";

        $offlineLog->delete();

        // If member no longer has any offline log records, revert offline_voter to false
        if ($member && !OfflineLog::where('member_id', $member->id)->exists()) {
            $member->update(['offline_voter' => false]);
            if (!empty($member->email)) {
                User::where('email', $member->email)->update(['offline_voter' => false]);
            }
        }

        return back()->with('success', "Data antrean #{$noAntrian} ({$memberName}) berhasil dihapus dari daftar pemilih TPS.");
    }

    /**
     * Legacy mark fallback for tests or backward compatibility.
     */
    public function mark(Request $request, Member $member)
    {
        if ($member->pollings()->exists()) {
            return back()->withErrors([
                'error' => "Anggota {$member->nama_lengkap} (KTA: {$member->no_kartu}) sudah memberikan suara secara online."
            ]);
        }

        $nextQueue = (OfflineLog::max('no_antrian') ?? 0) + 1;
        OfflineLog::firstOrCreate(
            ['member_id' => $member->id],
            [
                'nama_pengurus' => auth()->user()?->name ?? 'Admin Dashboard',
                'kode_akses' => 'ADMIN',
                'no_antrian' => $nextQueue,
                'voting_status' => OfflineLog::STATUS_ANTREAN,
            ]
        );

        $member->offline_voter = true;
        $member->save();

        if (!empty($member->email)) {
            User::where('email', $member->email)->update(['offline_voter' => true]);
        }
        ElectionQueue::where('member_id', $member->id)->delete();

        return back()->with('success', "{$member->nama_lengkap} berhasil ditandai sebagai Pemilih Offline.");
    }

    /**
     * Legacy unmark fallback for tests or backward compatibility.
     */
    public function unmark(Request $request, Member $member)
    {
        OfflineLog::where('member_id', $member->id)->delete();

        $member->offline_voter = false;
        $member->save();

        if (!empty($member->email)) {
            User::where('email', $member->email)->update(['offline_voter' => false]);
        }

        return back()->with('success', "Status Pemilih Offline untuk {$member->nama_lengkap} berhasil dibatalkan.");
    }

    /**
     * Legacy toggle fallback.
     */
    public function toggle(Request $request, Member $member)
    {
        if ($member->offline_voter) {
            return $this->unmark($request, $member);
        }
        return $this->mark($request, $member);
    }

    /**
     * Store a new offline vote recap entry.
     */
    public function storeRekap(Request $request)
    {
        $validated = $request->validate([
            'jumlah_suara'     => 'required|integer|min:1',
            'penanggung_jawab' => 'required|string|max:255',
            'calon_id'         => 'nullable|exists:calons,id',
            'keterangan'       => 'nullable|string|max:1000',
            'foto_bukti'       => 'nullable|file|mimes:jpeg,png,jpg,webp,pdf|max:10240',
        ], [
            'jumlah_suara.required'     => 'Jumlah suara wajib diisi.',
            'jumlah_suara.integer'      => 'Jumlah suara harus berupa angka bulat.',
            'jumlah_suara.min'          => 'Jumlah suara minimal 1 suara.',
            'penanggung_jawab.required' => 'Nama penanggung jawab wajib diisi.',
            'foto_bukti.file'           => 'File bukti harus berupa dokumen atau gambar.',
            'foto_bukti.mimes'          => 'Format file bukti harus jpeg, png, jpg, webp, atau pdf.',
            'foto_bukti.max'            => 'Ukuran file bukti maksimal 10 MB.',
        ]);

        $filePath = null;
        if ($request->hasFile('foto_bukti')) {
            $filePath = $request->file('foto_bukti')->store('polling_offline_proofs', 'public');
        }

        PollingOffline::create([
            'calon_id'         => $validated['calon_id'] ?? null,
            'jumlah_suara'     => (int) $validated['jumlah_suara'],
            'penanggung_jawab' => $validated['penanggung_jawab'],
            'keterangan'       => $validated['keterangan'] ?? null,
            'foto_bukti'       => $filePath,
            'created_by'       => auth()->id(),
        ]);

        Cache::forget('polling_throttled_state');

        return back()->with('success', "Rekapitulasi suara offline sebanyak {$validated['jumlah_suara']} suara berhasil disimpan.");
    }

    /**
     * Delete an offline vote recap entry.
     */
    public function destroyRekap(PollingOffline $pollingOffline)
    {
        $jumlah = $pollingOffline->jumlah_suara;
        if ($pollingOffline->foto_bukti && Storage::disk('public')->exists($pollingOffline->foto_bukti)) {
            Storage::disk('public')->delete($pollingOffline->foto_bukti);
        }

        $pollingOffline->delete();

        Cache::forget('polling_throttled_state');

        return back()->with('success', "Data rekapitulasi suara offline ({$jumlah} suara) berhasil dihapus.");
    }
}
