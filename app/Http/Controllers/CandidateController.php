<?php

namespace App\Http\Controllers;

use App\Models\Calon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CandidateController extends Controller
{
    public function index(Request $request)
    {
        $query = Calon::with('member')->orderBy('created_at', 'desc');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
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

        $candidates = $query->paginate(10)->withQueryString();

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

        $calon->update([
            'status' => $validated['status'],
        ]);

        return back()->with('success', 'Status calon berhasil diperbarui.');
    }

    public function destroy(Calon $calon)
    {
        $calon->delete();
        return back()->with('success', 'Calon berhasil dihapus dari daftar.');
    }
}
