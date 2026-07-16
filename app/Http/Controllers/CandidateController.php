<?php

namespace App\Http\Controllers;

use App\Models\Calon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CandidateController extends Controller
{
    public function index(Request $request)
    {
        $subQuery = Calon::select('member_id', \DB::raw('MAX(id) as max_id'))
            ->with('member');

        if ($search = $request->input('search')) {
            $subQuery->where(function ($q) use ($search) {
                $q->where('no_kartu', 'like', "%{$search}%")
                  ->orWhere('chapter', 'like', "%{$search}%")
                  ->orWhere('status', 'like', "%{$search}%")
                  ->orWhere('diajukan_oleh', 'like', "%{$search}%")
                  ->orWhereHas('member', function ($mq) use ($search) {
                      $mq->where('nama_lengkap', 'like', "%{$search}%")
                        ->orWhere('nama_panggilan', 'like', "%{$search}%");
                  });
            });
        }

        $groupedIds = $subQuery->groupBy('member_id')->pluck('max_id');

        $query = Calon::with('member')
            ->whereIn('id', $groupedIds)
            ->orderBy('created_at', 'desc');

        $candidates = $query->paginate(10)->withQueryString();

        $memberIds = $candidates->pluck('member_id');
        $allNominations = Calon::whereIn('member_id', $memberIds)->orderBy('created_at', 'desc')->get()->groupBy('member_id');

        $candidates->getCollection()->transform(function ($candidate) use ($allNominations) {
            $noms = $allNominations->get($candidate->member_id, collect());
            $candidate->total_nominations = $noms->count();
            $candidate->self_nominations = $noms->where('diajukan_oleh', 'self')->count();
            $candidate->member_nominations = $noms->where('diajukan_oleh', '!=', 'self')->count();
            $candidate->nominations_list = $noms->map(function ($n) {
                return [
                    'id' => $n->id,
                    'diajukan_oleh' => $n->diajukan_oleh,
                    'no_kartu_diajukan_oleh' => $n->no_kartu_diajukan_oleh,
                    'visi' => $n->visi,
                    'misi' => $n->misi,
                    'status' => $n->status,
                    'created_at' => $n->created_at,
                ];
            })->values();
            return $candidate;
        });

        return Inertia::render('Candidate/Index', [
            'candidates' => $candidates,
            'filters' => ['search' => $request->input('search', '')],
        ]);
    }

    public function updateStatus(Request $request, Calon $calon)
    {
        $validated = $request->validate([
            'status' => 'required|in:mengajukan,diajukan,ditetapkan,ditolak',
        ]);

        Calon::where('member_id', $calon->member_id)->update([
            'status' => $validated['status'],
        ]);

        return back()->with('success', 'Status calon berhasil diperbarui.');
    }

    public function destroy(Calon $calon)
    {
        Calon::where('member_id', $calon->member_id)->delete();
        return back()->with('success', 'Calon berhasil dihapus dari daftar.');
    }
}
