<?php

use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a member can be registered with valid data', function () {
    $response = $this->post('/member/register', [
        'nama_lengkap'      => 'Asep Sunandar',
        'nama_panggilan'    => 'Asep',
        'tempat_lahir'      => 'Bandung',
        'tanggal_lahir'     => '12/03/1990',
        'jenis_kelamin'     => 'L',
        'gol_darah'         => 'O',
        'nik'               => '1234567890123456',
        'alamat'            => 'Jl. Sudirman No. 1',
        'no_wa'             => '081234567890',
        'email'             => 'asep@example.com',
        'profesi'           => 'Wiraswasta',
        'no_kartu'          => '1234',
        'status_keanggotaan'=> 'PROSPECT',
        'chapter'           => 'Bandung',
        'terdaftar_sejak'   => '2024',
    ]);

    $response->assertRedirect('/member/register-success');
    $this->assertDatabaseHas('members', [
        'nik' => '1234567890123456',
        'no_wa' => '081234567890',
        'no_kartu' => '1234',
    ]);
});

test('member registration fails if NIK, WhatsApp number, or Card number is already registered', function () {
    // Create an existing member
    Member::create([
        'nama_lengkap'      => 'Existing Member',
        'nama_panggilan'    => 'Exist',
        'tempat_lahir'      => 'Jakarta',
        'tanggal_lahir'     => '01/01/1980',
        'jenis_kelamin'     => 'L',
        'gol_darah'         => 'A',
        'nik'               => '1234567890123456',
        'alamat'            => 'Jl. Jakarta',
        'no_wa'             => '081234567890',
        'email'             => 'existing@example.com',
        'no_kartu'          => '1234',
        'status_keanggotaan'=> 'LIFE MEMBER',
        'chapter'           => 'Jakarta',
    ]);

    // Try to register another member with the same NIK
    $response = $this->post('/member/register', [
        'nama_lengkap'      => 'Duplicate NIK',
        'nama_panggilan'    => 'Dupe',
        'tempat_lahir'      => 'Bandung',
        'tanggal_lahir'     => '12/03/1990',
        'jenis_kelamin'     => 'L',
        'gol_darah'         => 'O',
        'nik'               => '1234567890123456', // duplicate
        'alamat'            => 'Jl. Sudirman No. 1',
        'no_wa'             => '089999999999',
        'no_kartu'          => '5678',
        'status_keanggotaan'=> 'PROSPECT',
        'chapter'           => 'Bandung',
    ]);

    $response->assertSessionHasErrors(['nik']);

    // Try to register another member with the same WhatsApp number
    $response = $this->post('/member/register', [
        'nama_lengkap'      => 'Duplicate WA',
        'nama_panggilan'    => 'Dupe',
        'tempat_lahir'      => 'Bandung',
        'tanggal_lahir'     => '12/03/1990',
        'jenis_kelamin'     => 'L',
        'gol_darah'         => 'O',
        'nik'               => '9876543210987654',
        'alamat'            => 'Jl. Sudirman No. 1',
        'no_wa'             => '081234567890', // duplicate
        'no_kartu'          => '5678',
        'status_keanggotaan'=> 'PROSPECT',
        'chapter'           => 'Bandung',
    ]);

    $response->assertSessionHasErrors(['no_wa']);

    // Try to register another member with the same Card number
    $response = $this->post('/member/register', [
        'nama_lengkap'      => 'Duplicate Card',
        'nama_panggilan'    => 'Dupe',
        'tempat_lahir'      => 'Bandung',
        'tanggal_lahir'     => '12/03/1990',
        'jenis_kelamin'     => 'L',
        'gol_darah'         => 'O',
        'nik'               => '9876543210987654',
        'alamat'            => 'Jl. Sudirman No. 1',
        'no_wa'             => '089999999999',
        'no_kartu'          => '1234', // duplicate
        'status_keanggotaan'=> 'PROSPECT',
        'chapter'           => 'Bandung',
    ]);

    $response->assertSessionHasErrors(['no_kartu']);
});
