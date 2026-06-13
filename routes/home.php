<?php
use App\Http\Controllers\ElectionController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MemberController;

Route::group(['prefix' => '/member'] , function(){
    Route::get('/',[MemberController::class,'index'])->name('member.index');
    Route::get('/register',[MemberController::class,'register'])->name('member.register');
    Route::post('/register',[MemberController::class,'registerPost'])->name('member.register_post');
    Route::get('/register-success' , [MemberController::class,'registerSuccess'])->name('member.register_success');
    Route::get('/syarat-ketentuan', [MemberController::class, 'termsIntegrity'])->name('member.terms_integrity');
    Route::get('/kebijakan-privasi', [MemberController::class, 'termsIntegrity'])->name('member.privacy');
    Route::get('/{no_kartu}', [MemberController::class, 'showPublic'])->name('member.show');
});

Route::group(['prefix' => '/election'] , function(){
    Route::get('/', fn() => redirect('/election/portal'));
    Route::get('/portal' , [ElectionController::class,'portal'])->name('election.portal'); 
    Route::get('/polling', [ElectionController::class, 'livePolling'])->name('election.polling');
    Route::get('/search-members', [ElectionController::class, 'searchMembers'])->name('election.search_members');
    Route::get('/member-info/{nocard}', [ElectionController::class, 'getMemberInfo'])->name('election.member_info');
    Route::post('/nominate-self', [ElectionController::class, 'nominateSelf'])->name('election.nominate_self');
    Route::post('/nominate-member', [ElectionController::class, 'nominateMember'])->name('election.nominate_member');

    Route::get('/login' , [ElectionController::class, 'login'])->name('election.login');
    Route::post('/login', [ElectionController::class, 'loginPost'])->name('election.login_post');

    Route::group(['middleware' => [\App\Http\Middleware\ElectionAuth::class]], function() {
        Route::get('/dashboard', [ElectionController::class, 'dashboard'])->name('election.dashboard');
        Route::post('/vote', [ElectionController::class, 'vote'])->name('election.vote');
        Route::post('/logout', [ElectionController::class, 'logout'])->name('election.logout');
    });
});