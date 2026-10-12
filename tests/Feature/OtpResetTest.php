<?php

use App\Models\Member;
use App\Models\Otp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeMember(array $attributes = []) {
    return Member::create(array_merge([
        'nama_lengkap' => 'Member Test',
        'nama_panggilan' => 'Test',
        'tempat_lahir' => 'Bandung',
        'tanggal_lahir' => '01/01/1980',
        'jenis_kelamin' => 'L',
        'gol_darah' => 'O',
        'alamat' => 'Jl. Test No. 123',
        'no_wa' => '628123456789',
        'status_keanggotaan' => 'LIFE MEMBER',
        'chapter' => 'Mother Chapter',
        'no_kartu' => '0001',
        'terdaftar_sejak' => '2010',
    ], $attributes));
}

test('guest cannot access otp reset endpoints', function () {
    $this->post('/dashboard/otp-logs/reset-all')
        ->assertRedirect('/login');

    $this->post('/dashboard/otp-logs/reset-by-user', ['user_id' => 1])
        ->assertRedirect('/login');
});

test('reset all deletes unverified otps created today but keeps verified and older otps', function () {
    $admin = User::factory()->create();

    $member = makeMember();

    // 1. Unverified OTP created today (SHOULD BE DELETED)
    $todayUnverified = Otp::create([
        'member_id' => $member->id,
        'otp' => '111111',
        'phone' => '628123456789',
        'is_verified' => false,
        'expires_at' => now()->addMinutes(5),
    ]);

    // 2. Verified OTP created today (SHOULD NOT BE DELETED)
    $todayVerified = Otp::create([
        'member_id' => $member->id,
        'otp' => '222222',
        'phone' => '628123456789',
        'is_verified' => true,
        'expires_at' => now()->addMinutes(5),
    ]);

    // 3. Unverified OTP created yesterday (SHOULD NOT BE DELETED)
    $yesterdayUnverified = Otp::create([
        'member_id' => $member->id,
        'otp' => '333333',
        'phone' => '628123456789',
        'is_verified' => false,
        'expires_at' => now()->subDay()->addMinutes(5),
    ]);
    $yesterdayUnverified->created_at = now()->subDay();
    $yesterdayUnverified->save();

    $response = $this->actingAs($admin)->post('/dashboard/otp-logs/reset-all');

    $response->assertSessionHas('success');
    $this->assertDatabaseMissing('otps', ['id' => $todayUnverified->id]);
    $this->assertDatabaseHas('otps', ['id' => $todayVerified->id]);
    $this->assertDatabaseHas('otps', ['id' => $yesterdayUnverified->id]);
});

test('reset by user requires user_id', function () {
    $admin = User::factory()->create();

    $response = $this->actingAs($admin)->post('/dashboard/otp-logs/reset-by-user', []);
    $response->assertSessionHasErrors('user_id');
});

test('reset by user deletes today otps for specific user by member_id', function () {
    $admin = User::factory()->create();

    $memberA = makeMember([
        'nama_lengkap' => 'Member A',
        'nama_panggilan' => 'A',
        'no_kartu' => '0010',
        'no_wa' => '628111111111',
    ]);

    $memberB = makeMember([
        'nama_lengkap' => 'Member B',
        'nama_panggilan' => 'B',
        'no_kartu' => '0020',
        'no_wa' => '628222222222',
    ]);

    // Member A today OTPs
    $otpA1 = Otp::create([
        'member_id' => $memberA->id,
        'otp' => '100001',
        'phone' => '628111111111',
        'is_verified' => false,
        'expires_at' => now()->addMinutes(5),
    ]);
    $otpA2 = Otp::create([
        'member_id' => $memberA->id,
        'otp' => '100002',
        'phone' => '628111111111',
        'is_verified' => true,
        'expires_at' => now()->addMinutes(5),
    ]);

    // Member A yesterday OTP (SHOULD NOT BE DELETED)
    $otpAYesterday = Otp::create([
        'member_id' => $memberA->id,
        'otp' => '100003',
        'phone' => '628111111111',
        'is_verified' => false,
        'expires_at' => now()->subDay()->addMinutes(5),
    ]);
    $otpAYesterday->created_at = now()->subDay();
    $otpAYesterday->save();

    // Member B today OTP (SHOULD NOT BE DELETED)
    $otpB = Otp::create([
        'member_id' => $memberB->id,
        'otp' => '200001',
        'phone' => '628222222222',
        'is_verified' => false,
        'expires_at' => now()->addMinutes(5),
    ]);

    $response = $this->actingAs($admin)->post('/dashboard/otp-logs/reset-by-user', [
        'user_id' => $memberA->id,
    ]);

    $response->assertSessionHas('success');
    $this->assertDatabaseMissing('otps', ['id' => $otpA1->id]);
    $this->assertDatabaseMissing('otps', ['id' => $otpA2->id]);
    $this->assertDatabaseHas('otps', ['id' => $otpAYesterday->id]);
    $this->assertDatabaseHas('otps', ['id' => $otpB->id]);
});

test('reset by user works with user_id or member no_kartu', function () {
    $admin = User::factory()->create();

    $member = makeMember([
        'nama_lengkap' => 'Member Card Test',
        'nama_panggilan' => 'CardTest',
        'no_kartu' => '0777',
        'no_wa' => '628333333333',
    ]);

    $otp = Otp::create([
        'member_id' => $member->id,
        'otp' => '777777',
        'phone' => '628333333333',
        'is_verified' => false,
        'expires_at' => now()->addMinutes(5),
    ]);

    // Reset using card number '0777'
    $response = $this->actingAs($admin)->post('/dashboard/otp-logs/reset-by-user', [
        'user_id' => '0777',
    ]);

    $response->assertSessionHas('success');
    $this->assertDatabaseMissing('otps', ['id' => $otp->id]);
});

test('reset by user deletes records with matching user_id column', function () {
    $admin = User::factory()->create();

    $otp = Otp::create([
        'user_id' => 999,
        'otp' => '999999',
        'phone' => '628999999999',
        'is_verified' => false,
        'expires_at' => now()->addMinutes(5),
    ]);

    $response = $this->actingAs($admin)->post('/dashboard/otp-logs/reset-by-user', [
        'user_id' => 999,
    ]);

    $response->assertSessionHas('success');
    $this->assertDatabaseMissing('otps', ['id' => $otp->id]);
});
