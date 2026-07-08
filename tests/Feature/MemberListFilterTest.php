<?php

use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guest cannot access member list', function () {
    $response = $this->get('/dashboard/member');
    $response->assertRedirect('/login');
});

test('authenticated user can view member list without filters', function () {
    $user = User::factory()->create();
    
    Member::create([
        'nama_lengkap' => 'Ahmad Fauzi',
        'nama_panggilan' => 'Ahmad',
        'tempat_lahir' => 'Bandung',
        'tanggal_lahir' => '01/01/1980',
        'jenis_kelamin' => 'L',
        'gol_darah' => 'O',
        'nik' => '1234567890123456',
        'alamat' => 'Jl. Test',
        'no_wa' => '628123456789',
        'status_keanggotaan' => 'LIFE MEMBER',
        'chapter' => 'Mother Chapter',
        'no_kartu' => '0001',
        'terdaftar_sejak' => '2010',
    ]);

    $response = $this->actingAs($user)->get('/dashboard/member');
    $response->assertStatus(200);
    $response->assertSee('Ahmad Fauzi');
});

test('member list can be filtered by Life Member duration', function () {
    $user = User::factory()->create();
    $currentYear = intval(date('Y'));

    // 1. Life Member >= 10 years (e.g. 11 years ago)
    $m1 = Member::create([
        'nama_lengkap' => 'LM Ten Plus',
        'nama_panggilan' => 'Ten',
        'tempat_lahir' => 'Bandung',
        'tanggal_lahir' => '01/01/1980',
        'jenis_kelamin' => 'L',
        'gol_darah' => 'O',
        'nik' => '1234567890123401',
        'alamat' => 'Jl. Test',
        'no_wa' => '628123456701',
        'status_keanggotaan' => 'LIFE MEMBER',
        'chapter' => 'Mother Chapter',
        'no_kartu' => '0001',
        'terdaftar_sejak' => (string)($currentYear - 11),
    ]);

    // 2. Life Member >= 5 years but < 10 years (e.g. 6 years ago)
    $m2 = Member::create([
        'nama_lengkap' => 'LM Five Plus',
        'nama_panggilan' => 'Five',
        'tempat_lahir' => 'Bandung',
        'tanggal_lahir' => '01/01/1980',
        'jenis_kelamin' => 'L',
        'gol_darah' => 'O',
        'nik' => '1234567890123402',
        'alamat' => 'Jl. Test',
        'no_wa' => '628123456702',
        'status_keanggotaan' => 'LIFE MEMBER',
        'chapter' => 'Mother Chapter',
        'no_kartu' => '0002',
        'terdaftar_sejak' => (string)($currentYear - 6),
    ]);

    // 3. Life Member < 5 years (e.g. 2 years ago)
    $m3 = Member::create([
        'nama_lengkap' => 'LM Young',
        'nama_panggilan' => 'Young',
        'tempat_lahir' => 'Bandung',
        'tanggal_lahir' => '01/01/1980',
        'jenis_kelamin' => 'L',
        'gol_darah' => 'O',
        'nik' => '1234567890123403',
        'alamat' => 'Jl. Test',
        'no_wa' => '628123456703',
        'status_keanggotaan' => 'LIFE MEMBER',
        'chapter' => 'Mother Chapter',
        'no_kartu' => '0003',
        'terdaftar_sejak' => (string)($currentYear - 2),
    ]);

    // 4. Non-Life Member >= 10 years (should not show up in LM filters)
    $m4 = Member::create([
        'nama_lengkap' => 'Prospect Old',
        'nama_panggilan' => 'Prospect',
        'tempat_lahir' => 'Bandung',
        'tanggal_lahir' => '01/01/1980',
        'jenis_kelamin' => 'L',
        'gol_darah' => 'O',
        'nik' => '1234567890123404',
        'alamat' => 'Jl. Test',
        'no_wa' => '628123456704',
        'status_keanggotaan' => 'PROSPECT',
        'chapter' => 'Mother Chapter',
        'no_kartu' => '0004',
        'terdaftar_sejak' => (string)($currentYear - 12),
    ]);

    // Test filter_lm = 10
    $response = $this->actingAs($user)->get('/dashboard/member?filter_lm=10');
    $response->assertStatus(200);
    $response->assertSee('LM Ten Plus');
    $response->assertDontSee('LM Five Plus');
    $response->assertDontSee('LM Young');
    $response->assertDontSee('Prospect Old');

    // Test filter_lm = 10_under
    $response = $this->actingAs($user)->get('/dashboard/member?filter_lm=10_under');
    $response->assertStatus(200);
    $response->assertDontSee('LM Ten Plus');
    $response->assertSee('LM Five Plus');
    $response->assertSee('LM Young');
    $response->assertDontSee('Prospect Old');
});

test('member list can be searched by nama panggilan', function () {
    $user = User::factory()->create();

    Member::create([
        'nama_lengkap' => 'Budi Santoso',
        'nama_panggilan' => 'Gonggong',
        'tempat_lahir' => 'Bandung',
        'tanggal_lahir' => '01/01/1980',
        'jenis_kelamin' => 'L',
        'gol_darah' => 'O',
        'nik' => '1234567890123999',
        'alamat' => 'Jl. Test',
        'no_wa' => '628123456799',
        'status_keanggotaan' => 'LIFE MEMBER',
        'chapter' => 'Mother Chapter',
        'no_kartu' => '0999',
        'terdaftar_sejak' => '2015',
    ]);

    $response = $this->actingAs($user)->get('/dashboard/member?search=Gonggong');
    $response->assertStatus(200);
    $response->assertSee('Budi Santoso');
});

test('member list can be sorted by no_kartu ascending and descending', function () {
    $user = User::factory()->create();

    Member::create([
        'nama_lengkap' => 'Anggota Satu',
        'nama_panggilan' => 'Satu',
        'tempat_lahir' => 'Bandung',
        'tanggal_lahir' => '01/01/1980',
        'jenis_kelamin' => 'L',
        'gol_darah' => 'O',
        'nik' => '1234567890111111',
        'alamat' => 'Jl. Satu',
        'no_wa' => '628111111111',
        'status_keanggotaan' => 'LIFE MEMBER',
        'chapter' => 'Mother Chapter',
        'no_kartu' => '0010',
        'terdaftar_sejak' => '2015',
    ]);

    Member::create([
        'nama_lengkap' => 'Anggota Dua',
        'nama_panggilan' => 'Dua',
        'tempat_lahir' => 'Bandung',
        'tanggal_lahir' => '01/01/1980',
        'jenis_kelamin' => 'L',
        'gol_darah' => 'O',
        'nik' => '1234567890222222',
        'alamat' => 'Jl. Dua',
        'no_wa' => '628222222222',
        'status_keanggotaan' => 'LIFE MEMBER',
        'chapter' => 'Mother Chapter',
        'no_kartu' => '0090',
        'terdaftar_sejak' => '2016',
    ]);

    $responseAsc = $this->actingAs($user)->get('/dashboard/member?sort_by=no_kartu&sort_dir=asc');
    $responseAsc->assertStatus(200);
    $membersAsc = $responseAsc->original->getData()['page']['props']['members']['data'];
    expect($membersAsc[0]['no_kartu'])->toBe('0010');
    expect($membersAsc[1]['no_kartu'])->toBe('0090');

    $responseDesc = $this->actingAs($user)->get('/dashboard/member?sort_by=no_kartu&sort_dir=desc');
    $responseDesc->assertStatus(200);
    $membersDesc = $responseDesc->original->getData()['page']['props']['members']['data'];
    expect($membersDesc[0]['no_kartu'])->toBe('0090');
    expect($membersDesc[1]['no_kartu'])->toBe('0010');
});

