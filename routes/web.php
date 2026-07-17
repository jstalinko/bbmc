<?php

use App\Http\Controllers\MemberController;
use App\Http\Controllers\CandidateController;
use App\Http\Controllers\ElectionController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');


Route::group(['prefix' => 'dashboard', 'middleware' => ['auth', 'verified']], function () {
    Route::get('/', function () {
        $totalMembers = \App\Models\Member::count();
        $totalCandidates = \App\Models\Calon::distinct()->count('member_id');
        $totalCandidatesDitetapkan = \App\Models\Calon::where('status', 'ditetapkan')->distinct()->count('member_id');
        $totalLifeMembers = \App\Models\Member::where('status_keanggotaan', 'LIFE MEMBER')->count();
        $latestMembers = \App\Models\Member::orderBy('created_at', 'desc')->take(5)->get();

        return Inertia::render('Dashboard', [
            'stats' => [
                'total_members' => $totalMembers,
                'total_candidates' => $totalCandidates,
                'total_candidates_ditetapkan' => $totalCandidatesDitetapkan,
                'total_life_members' => $totalLifeMembers,
            ],
            'latest_members' => $latestMembers,
        ]);
    })->name('dashboard');

    Route::get('/member', [MemberController::class, 'list'])->name('member.list');
    Route::get('/member/penalty', [MemberController::class, 'penaltyList'])->name('member.penalty');
    Route::get('/member/export/pdf', [MemberController::class, 'exportPdf'])->name('member.export.pdf');
    Route::get('/member/export/csv', [MemberController::class, 'exportCsv'])->name('member.export.csv');
    Route::get('/member/{member}/print', [MemberController::class, 'printCard'])->name('member.print');
    Route::get('/member/{member}/print-pdf', [MemberController::class, 'printCardPdf'])->name('member.print.pdf');
    Route::get('/member/{member}', [MemberController::class, 'show'])->name('member.show');
    Route::put('/member/{member}', [MemberController::class, 'update'])->name('member.update');
    Route::put('/member/{member}/penalty', [MemberController::class, 'updatePenalty'])->name('member.update_penalty');
    Route::delete('/member/{member}', [MemberController::class, 'destroy'])->name('member.destroy');

    Route::get('/candidate' , [CandidateController::class,'index'])->name('candidate.list');
    Route::put('/candidate/{calon}', [CandidateController::class, 'updateStatus'])->name('candidate.update');
    Route::delete('/candidate/{calon}', [CandidateController::class, 'destroy'])->name('candidate.destroy');

    Route::get('/setting-pemilihan', [ElectionController::class, 'setting'])->name('election.setting');
    Route::post('/setting-pemilihan', [ElectionController::class, 'settingPost'])->name('election.setting_post');

    Route::get('/whatsapp', [\App\Http\Controllers\WhatsappController::class, 'index'])->name('whatsapp.index');
    Route::post('/whatsapp/send', [\App\Http\Controllers\WhatsappController::class, 'send'])->name('whatsapp.send');
    Route::get('/whatsapp/status/{batchId}', [\App\Http\Controllers\WhatsappController::class, 'status'])->name('whatsapp.status');
});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
require __DIR__ . '/home.php';