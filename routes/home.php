<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MemberController;

Route::group(['prefix' => '/member'] , function(){
    Route::get('/',[MemberController::class,'index'])->name('member.index');
    Route::get('/register',[MemberController::class,'register'])->name('member.register');
    Route::post('/register',[MemberController::class,'registerPost'])->name('member.register_post');
    Route::get('/register-success' , [MemberController::class,'registerSuccess'])->name('member.register_success');
});