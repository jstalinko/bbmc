<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Member;
use App\Http\Controllers\ElectionController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::get('/validate-nocard/{nocard}' , function (Request $request){
    $nocard = $request->nocard;
    $member = Member::where('no_kartu', $nocard)->first();
    if (!$member) return response()->json(['available' => true]);
    return response()->json(['available' => false]);
});

Route::post('/send-otp', [ElectionController::class, 'sendOtp'])->name('election.send_otp');