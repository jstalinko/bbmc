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
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::get('/member', [MemberController::class, 'list'])->name('member.list');
    Route::get('/member/{member}/print', [MemberController::class, 'printCard'])->name('member.print');
    Route::get('/member/{member}/print-pdf', [MemberController::class, 'printCardPdf'])->name('member.print.pdf');
    Route::get('/member/{member}', [MemberController::class, 'show'])->name('member.show');
    Route::put('/member/{member}', [MemberController::class, 'update'])->name('member.update');
    Route::delete('/member/{member}', [MemberController::class, 'destroy'])->name('member.destroy');

    Route::get('/candidate' , [CandidateController::class,'index'])->name('candidate.list');
    Route::put('/candidate/{calon}', [CandidateController::class, 'updateStatus'])->name('candidate.update');
    Route::delete('/candidate/{calon}', [CandidateController::class, 'destroy'])->name('candidate.destroy');

    Route::get('/setting-pemilihan', [ElectionController::class, 'setting'])->name('election.setting');
    Route::post('/setting-pemilihan', [ElectionController::class, 'settingPost'])->name('election.setting_post');
});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
require __DIR__ . '/home.php';