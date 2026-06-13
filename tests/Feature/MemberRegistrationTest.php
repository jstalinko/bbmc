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
        'agreed'            => true,
    ]);

    $response->assertRedirect('/member/register-success');
    $this->assertDatabaseHas('members', [
        'nik' => '1234567890123456',
        'no_wa' => '081234567890',
        'no_kartu' => '1234',
    ]);
});

test('a member can be registered without NIK', function () {
    $response = $this->post('/member/register', [
        'nama_lengkap'      => 'Budi Prasetyo',
        'nama_panggilan'    => 'Budi',
        'tempat_lahir'      => 'Jakarta',
        'tanggal_lahir'     => '05/08/1992',
        'jenis_kelamin'     => 'L',
        'gol_darah'         => 'A',
        'alamat'            => 'Jl. Merdeka No. 10',
        'no_wa'             => '089876543210',
        'email'             => 'budi@example.com',
        'no_kartu'          => '5678',
        'status_keanggotaan'=> 'PROSPECT',
        'chapter'           => 'Jakarta',
        'agreed'            => true,
    ]);

    $response->assertRedirect('/member/register-success');
    $this->assertDatabaseHas('members', [
        'nama_lengkap' => 'Budi Prasetyo',
        'nik' => null,
        'no_wa' => '089876543210',
        'no_kartu' => '5678',
    ]);
});

test('member registration fails if terms checkbox is not agreed', function () {
    $response = $this->post('/member/register', [
        'nama_lengkap'      => 'Budi Prasetyo',
        'nama_panggilan'    => 'Budi',
        'tempat_lahir'      => 'Jakarta',
        'tanggal_lahir'     => '05/08/1992',
        'jenis_kelamin'     => 'L',
        'gol_darah'         => 'A',
        'alamat'            => 'Jl. Merdeka No. 10',
        'no_wa'             => '089876543210',
        'email'             => 'budi@example.com',
        'status_keanggotaan'=> 'PROSPECT',
        'chapter'           => 'Jakarta',
        'agreed'            => false, // not checked
    ]);

    $response->assertSessionHasErrors(['agreed']);
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
        'agreed'            => true,
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
        'agreed'            => true,
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
        'agreed'            => true,
    ]);

    $response->assertSessionHasErrors(['no_kartu']);
});

test('member registration fails if no_kartu is missing, not numeric, or not exactly 4 digits', function () {
    // 1. Missing no_kartu
    $response = $this->post('/member/register', [
        'nama_lengkap'      => 'Test Card',
        'nama_panggilan'    => 'Test',
        'tempat_lahir'      => 'Bandung',
        'tanggal_lahir'     => '12/03/1990',
        'jenis_kelamin'     => 'L',
        'gol_darah'         => 'O',
        'alamat'            => 'Jl. Sudirman No. 1',
        'no_wa'             => '089999999998',
        // 'no_kartu' missing
        'status_keanggotaan'=> 'PROSPECT',
        'chapter'           => 'Bandung',
        'agreed'            => true,
    ]);
    $response->assertSessionHasErrors(['no_kartu']);

    // 2. Non-numeric no_kartu
    $response = $this->post('/member/register', [
        'nama_lengkap'      => 'Test Card',
        'nama_panggilan'    => 'Test',
        'tempat_lahir'      => 'Bandung',
        'tanggal_lahir'     => '12/03/1990',
        'jenis_kelamin'     => 'L',
        'gol_darah'         => 'O',
        'alamat'            => 'Jl. Sudirman No. 1',
        'no_wa'             => '089999999998',
        'no_kartu'          => 'abcd', // non-numeric
        'status_keanggotaan'=> 'PROSPECT',
        'chapter'           => 'Bandung',
        'agreed'            => true,
    ]);
    $response->assertSessionHasErrors(['no_kartu']);

    // 3. Short no_kartu (not exactly 4 digits)
    $response = $this->post('/member/register', [
        'nama_lengkap'      => 'Test Card',
        'nama_panggilan'    => 'Test',
        'tempat_lahir'      => 'Bandung',
        'tanggal_lahir'     => '12/03/1990',
        'jenis_kelamin'     => 'L',
        'gol_darah'         => 'O',
        'alamat'            => 'Jl. Sudirman No. 1',
        'no_wa'             => '089999999998',
        'no_kartu'          => '123', // 3 digits
        'status_keanggotaan'=> 'PROSPECT',
        'chapter'           => 'Bandung',
        'agreed'            => true,
    ]);
    $response->assertSessionHasErrors(['no_kartu']);
});

test('a member can be updated successfully via dashboard', function () {
    $user = \App\Models\User::factory()->create();
    $member = Member::create([
        'nama_lengkap'      => 'Asep Sunandar',
        'nama_panggilan'    => 'Asep',
        'tempat_lahir'      => 'Bandung',
        'tanggal_lahir'     => '12/03/1990',
        'jenis_kelamin'     => 'L',
        'gol_darah'         => 'O',
        'alamat'            => 'Jl. Sudirman No. 1',
        'no_wa'             => '081234567890',
        'no_kartu'          => '1234',
        'status_keanggotaan'=> 'PROSPECT',
        'chapter'           => 'Bandung',
        'nik'               => null, // no NIK
    ]);

    $response = $this->actingAs($user)->put("/dashboard/member/{$member->id}", [
        'nama_lengkap'      => 'Asep Sunandar Updated',
        'nama_panggilan'    => 'Asep',
        'tempat_lahir'      => 'Bandung',
        'tanggal_lahir'     => '12/03/1990',
        'jenis_kelamin'     => 'L',
        'gol_darah'         => 'O',
        'alamat'            => 'Jl. Sudirman No. 1',
        'no_wa'             => '081234567890',
        'no_kartu'          => '1234',
        'status_keanggotaan'=> 'PROSPECT',
        'chapter'           => 'Bandung',
        'nik'               => '', // empty string
    ]);

    $response->assertSessionHasNoErrors();
});

test('it renders the member validation page', function () {
    $response = $this->get('/member/validate');
    $response->assertStatus(200);
});

test('member validation fails if no_kartu is not exactly 4 digits or empty', function () {
    // Empty no_kartu
    $response = $this->get('/member/validate?no_kartu=');
    $response->assertSessionHasErrors(['no_kartu']);

    // Non-4-digits no_kartu
    $response = $this->get('/member/validate?no_kartu=12');
    $response->assertSessionHasErrors(['no_kartu']);
});

test('member validation fails if member does not exist', function () {
    $response = $this->get('/member/validate?no_kartu=9999');
    $response->assertSessionHasErrors(['no_kartu']);
});

test('member validation redirects to public member show page when card number exists', function () {
    $member = Member::create([
        'nama_lengkap'      => 'Asep Sunandar',
        'nama_panggilan'    => 'Asep',
        'tempat_lahir'      => 'Bandung',
        'tanggal_lahir'     => '12/03/1990',
        'jenis_kelamin'     => 'L',
        'gol_darah'         => 'O',
        'alamat'            => 'Jl. Sudirman No. 1',
        'no_wa'             => '081234567890',
        'no_kartu'          => '1234',
        'status_keanggotaan'=> 'PROSPECT',
        'chapter'           => 'Bandung',
    ]);

    $response = $this->get('/member/validate?no_kartu=1234');
    $response->assertRedirect('/member/1234');
});

