<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Polling;
use App\Models\User;
use App\Models\ElectionQueue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class OfflineElectionController extends Controller
{
    /**
     * Display a listing of members with offline voting status and search/filter.
     */
    public function index(Request $request)
    {
        $query = Member::query()->withExists('pollings');

        // Search by Nama Lengkap, Nama Panggilan, or No. Kartu (KTA)
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $paddedNocard = str_pad($search, 4, '0', STR_PAD_LEFT);
            $query->where(function ($q) use ($search, $paddedNocard) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                  ->orWhere('nama_panggilan', 'like', "%{$search}%")
                  ->orWhere('no_kartu', 'like', "%{$search}%")
                  ->orWhere('no_kartu', $paddedNocard)
                  ->orWhere('no_wa', 'like', "%{$search}%");
            });
        }

        // Filter by offline voter status
        if ($request->filled('status_offline')) {
            $statusOffline = $request->input('status_offline');
            if ($statusOffline === 'offline') {
                $query->where('offline_voter', true);
            } elseif ($statusOffline === 'not_offline') {
                $query->where(function ($q) {
                    $q->where('offline_voter', false)->orWhereNull('offline_voter');
                });
            }
        }

        // Filter by membership status
        if ($request->filled('status_keanggotaan') && $request->input('status_keanggotaan') !== 'all') {
            $query->where('status_keanggotaan', $request->input('status_keanggotaan'));
        }

        // Sorting
        $allowedSorts = ['no_kartu', 'nama_lengkap', 'status_keanggotaan', 'chapter', 'offline_voter', 'created_at'];
        $sortBy = $request->input('sort_by');
        $sortDir = strtolower($request->input('sort_dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        if ($sortBy && in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir)->orderBy('id', 'asc');
        } else {
            // Default: offline voters on top or card number asc
            $query->orderBy('offline_voter', 'desc')->orderBy('no_kartu', 'asc');
        }

        $members = $query->paginate(15)->withQueryString();

        // Calculate statistics for quick insights
        $stats = [
            'total_offline_voters' => Member::where('offline_voter', true)->count(),
            'total_online_voted' => Polling::distinct('member_id')->count('member_id'),
            'total_eligible' => Member::where(function ($q) {
                $q->whereRaw('UPPER(status_keanggotaan) = ?', ['LIFE MEMBER'])
                  ->orWhereRaw('UPPER(status_keanggotaan) = ?', ['SS DIPONEGORO']);
            })->where(function ($q) {
                $q->whereNull('penalty')
                  ->orWhere('penalty', '')
                  ->orWhere('penalty', 'clean');
            })->count(),
            'total_members' => Member::count(),
        ];

        return Inertia::render('Election/Offline', [
            'members' => $members,
            'stats' => $stats,
            'filters' => [
                'search' => $request->input('search', ''),
                'status_offline' => $request->input('status_offline', 'all'),
                'status_keanggotaan' => $request->input('status_keanggotaan', 'all'),
                'sort_by' => $request->input('sort_by', ''),
                'sort_dir' => $request->input('sort_dir', ''),
            ],
        ]);
    }

    /**
     * Toggle offline voter status for a member.
     */
    public function toggle(Request $request, Member $member)
    {
        if ($member->offline_voter) {
            return $this->unmark($request, $member);
        }

        return $this->mark($request, $member);
    }

    /**
     * Mark a member as offline voter.
     */
    public function mark(Request $request, Member $member)
    {
        // Check if member has already voted online
        if ($member->pollings()->exists()) {
            return back()->withErrors([
                'error' => "Anggota {$member->nama_lengkap} (KTA: {$member->no_kartu}) sudah memberikan suara secara online dan tidak dapat ditandai sebagai pemilih offline."
            ]);
        }

        $member->offline_voter = true;
        $member->save();

        // Also sync to users table if any matching email or name exists
        if (!empty($member->email)) {
            User::where('email', $member->email)->update(['offline_voter' => true]);
        }

        // Clear any active election queue or session for this member
        ElectionQueue::where('member_id', $member->id)->delete();

        return back()->with([
            'success' => "{$member->nama_lengkap} (KTA: {$member->no_kartu}) berhasil ditandai sebagai Pemilih Offline. Akses login online telah dinonaktifkan."
        ]);
    }

    /**
     * Unmark a member from offline voter status.
     */
    public function unmark(Request $request, Member $member)
    {
        $member->offline_voter = false;
        $member->save();

        // Also sync to users table if any matching email exists
        if (!empty($member->email)) {
            User::where('email', $member->email)->update(['offline_voter' => false]);
        }

        return back()->with([
            'success' => "Status Pemilih Offline untuk {$member->nama_lengkap} (KTA: {$member->no_kartu}) berhasil dibatalkan. Anggota dapat kembali login online jika memenuhi syarat."
        ]);
    }
}
