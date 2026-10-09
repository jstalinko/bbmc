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
            ->select('calons.*')
            ->addSelect(['total_nominations_count' => \DB::table('calons as c')
                ->selectRaw('count(*)')
                ->whereColumn('c.member_id', 'calons.member_id')
            ])
            ->whereIn('id', $groupedIds)
            ->orderByRaw('CASE WHEN no_urut IS NULL OR no_urut = 0 THEN 1 ELSE 0 END, no_urut ASC')
            ->orderBy('total_nominations_count', 'desc')
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

        $assignedNoUruts = Calon::where('status', 'ditetapkan')
            ->whereNotNull('no_urut')
            ->pluck('no_urut')
            ->unique()
            ->sort()
            ->values();

        return Inertia::render('Candidate/Index', [
            'candidates' => $candidates,
            'assignedNoUruts' => $assignedNoUruts,
            'filters' => ['search' => $request->input('search', '')],
        ]);
    }

    public function updateStatus(Request $request, Calon $calon)
    {
        $targetStatus = $request->input('status') ?? $calon->status;

        $rules = [
            'status' => 'nullable|in:mengajukan,diajukan,ditetapkan,ditolak',
        ];

        if ($targetStatus === 'ditetapkan') {
            $rules['no_urut'] = [
                'required',
                'integer',
                'min:1',
                function ($attribute, $value, $fail) use ($calon) {
                    $exists = Calon::where('status', 'ditetapkan')
                        ->where('member_id', '!=', $calon->member_id)
                        ->where('no_urut', $value)
                        ->exists();
                    if ($exists) {
                        $fail("Nomor urut {$value} sudah digunakan oleh calon lain yang telah ditetapkan.");
                    }
                },
            ];
        } else {
            $rules['no_urut'] = 'nullable|integer|min:1';
        }

        $validated = $request->validate($rules, [
            'no_urut.required' => 'Nomor urut wajib diisi ketika calon ditetapkan.',
            'no_urut.integer' => 'Nomor urut harus berupa angka.',
            'no_urut.min' => 'Nomor urut minimal adalah 1.',
        ]);

        $updateData = [];
        if (!empty($validated['status'])) {
            $updateData['status'] = $validated['status'];
        }

        if ($targetStatus === 'ditetapkan') {
            $updateData['no_urut'] = (int) $validated['no_urut'];
        } elseif ($targetStatus === 'ditolak') {
            $updateData['no_urut'] = null;
        } elseif ($request->has('no_urut')) {
            $updateData['no_urut'] = $validated['no_urut'] !== '' && $validated['no_urut'] !== null ? (int)$validated['no_urut'] : null;
        }

        if (!empty($updateData)) {
            Calon::where('member_id', $calon->member_id)->update($updateData);
        }

        return back()->with('success', 'Data calon berhasil diperbarui.');
    }

    public function destroy(Calon $calon)
    {
        Calon::where('member_id', $calon->member_id)->delete();
        return back()->with('success', 'Calon berhasil dihapus dari daftar.');
    }
}
