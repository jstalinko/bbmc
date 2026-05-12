<?php

use App\Http\Controllers\MemberController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect('/member/register');
})->name('home');


Route::group(['prefix' => 'dashboard', 'middleware' => ['auth', 'verified']], function () {
    Route::get('/', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::get('/member', [MemberController::class, 'list'])->name('member.list');
    Route::get('/member/{member}', [MemberController::class, 'show'])->name('member.show');
    Route::put('/member/{member}', [MemberController::class, 'update'])->name('member.update');
    Route::delete('/member/{member}', [MemberController::class, 'destroy'])->name('member.destroy');
});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
require __DIR__ . '/home.php';