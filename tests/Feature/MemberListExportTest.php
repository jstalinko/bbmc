<?php

use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guest cannot access member list exports', function () {
    $responseCsv = $this->get('/dashboard/member/export/csv');
    $responseCsv->assertRedirect('/login');

    $responsePdf = $this->get('/dashboard/member/export/pdf');
    $responsePdf->assertRedirect('/login');
});

test('authenticated user can export member list to CSV', function () {
    $user = User::factory()->create();
    
    Member::create([
        'nama_lengkap' => 'Ahmad Export CSV',
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

    $response = $this->actingAs($user)->get('/dashboard/member/export/csv');
    
    $response->assertStatus(200);
    $response->assertHeader('Content-Disposition', 'attachment; filename="data_anggota_' . date('Ymd_His') . '.csv"');
    
    $content = $response->streamedContent();
    expect($content)->toContain('Ahmad Export CSV');
    expect($content)->toContain('LIFE MEMBER');
    expect($content)->toContain('Mother Chapter');
});

test('member list export CSV respects filters', function () {
    $user = User::factory()->create();
    $currentYear = intval(date('Y'));

    // 1. Life Member >= 10 years (e.g. 11 years ago)
    Member::create([
        'nama_lengkap' => 'LM Ten Plus Export',
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
    Member::create([
        'nama_lengkap' => 'LM Five Plus Export',
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

    // Test CSV export with filter_lm = 10
    $response = $this->actingAs($user)->get('/dashboard/member/export/csv?filter_lm=10');
    $response->assertStatus(200);
    
    $content = $response->streamedContent();
    expect($content)->toContain('LM Ten Plus Export');
    expect($content)->not->toContain('LM Five Plus Export');
});

test('authenticated user can export member list to PDF', function () {
    $user = User::factory()->create();
    
    Member::create([
        'nama_lengkap' => 'Ahmad Export PDF',
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

    $response = $this->actingAs($user)->get('/dashboard/member/export/pdf');
    
    $response->assertStatus(200);
    $response->assertHeader('Content-Disposition', 'attachment; filename=daftar_anggota_' . date('Ymd_His') . '.pdf');
});
