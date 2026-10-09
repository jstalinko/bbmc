<?php

use App\Models\Calon;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createTestCandidate(array $attributes = []) {
    $member = Member::create(array_merge([
        'nama_lengkap' => 'Candidate ' . uniqid(),
        'nama_panggilan' => 'Cand',
        'tempat_lahir' => 'Bandung',
        'tanggal_lahir' => '01/01/1990',
        'jenis_kelamin' => 'L',
        'gol_darah' => 'O',
        'nik' => '1234567890' . rand(100000, 999999),
        'alamat' => 'Jl. Calon',
        'no_wa' => '628123456' . rand(100, 999),
        'email' => 'calon' . uniqid() . '@example.com',
        'instagram' => 'calon_ig',
        'facebook' => 'calon_fb',
        'pekerjaan' => 'Swasta',
        'status_pernikahan' => 'Belum Menikah',
        'nama_istri' => null,
        'chapter' => 'Bandung',
        'checkpoint' => 'Checkpoint 1',
        'region' => 'West Java',
        'status_keanggotaan' => 'LIFE MEMBER',
        'no_kartu' => sprintf('%04d', rand(1, 9999)),
        'tanggal_registrasi' => '01/01/2010',
    ], $attributes['member'] ?? []));

    return Calon::create(array_merge([
        'member_id' => $member->id,
        'no_kartu' => $member->no_kartu,
        'chapter' => $member->chapter,
        'visi' => 'Visi Calon',
        'misi' => 'Misi Calon',
        'status' => 'mengajukan',
        'diajukan_oleh' => 'self',
        'no_kartu_diajukan_oleh' => null,
        'foto_calon' => null,
    ], array_diff_key($attributes, ['member' => true])));
}

test('guest cannot access candidate dashboard', function () {
    $response = $this->get('/dashboard/candidate');
    $response->assertRedirect('/login');
});

test('authenticated user can view candidate list', function () {
    $user = User::factory()->create();
    createTestCandidate();

    $response = $this->actingAs($user)->get('/dashboard/candidate');
    $response->assertStatus(200);
});

test('cannot set candidate to ditetapkan without no_urut', function () {
    $user = User::factory()->create();
    $calon = createTestCandidate();

    $response = $this->actingAs($user)->put("/dashboard/candidate/{$calon->id}", [
        'status' => 'ditetapkan',
        'no_urut' => null,
    ]);

    $response->assertSessionHasErrors(['no_urut']);
    expect($calon->fresh()->status)->toBe('mengajukan');
});

test('cannot set candidate to ditetapkan with empty no_urut', function () {
    $user = User::factory()->create();
    $calon = createTestCandidate();

    $response = $this->actingAs($user)->put("/dashboard/candidate/{$calon->id}", [
        'status' => 'ditetapkan',
        'no_urut' => '',
    ]);

    $response->assertSessionHasErrors(['no_urut']);
    expect($calon->fresh()->status)->toBe('mengajukan');
});

test('cannot set candidate to ditetapkan with no_urut less than 1', function () {
    $user = User::factory()->create();
    $calon = createTestCandidate();

    $response = $this->actingAs($user)->put("/dashboard/candidate/{$calon->id}", [
        'status' => 'ditetapkan',
        'no_urut' => 0,
    ]);

    $response->assertSessionHasErrors(['no_urut']);
    expect($calon->fresh()->status)->toBe('mengajukan');
});

test('cannot use duplicate no_urut among ditetapkan candidates', function () {
    $user = User::factory()->create();
    $calon1 = createTestCandidate(['status' => 'ditetapkan', 'no_urut' => 1]);
    $calon2 = createTestCandidate(['status' => 'mengajukan']);

    $response = $this->actingAs($user)->put("/dashboard/candidate/{$calon2->id}", [
        'status' => 'ditetapkan',
        'no_urut' => 1,
    ]);

    $response->assertSessionHasErrors(['no_urut']);
    expect($calon2->fresh()->status)->toBe('mengajukan');
});

test('can set candidate to ditetapkan with valid no_urut', function () {
    $user = User::factory()->create();
    $calon = createTestCandidate();

    $response = $this->actingAs($user)->put("/dashboard/candidate/{$calon->id}", [
        'status' => 'ditetapkan',
        'no_urut' => 1,
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertSessionHas('success');

    $freshCalon = $calon->fresh();
    expect($freshCalon->status)->toBe('ditetapkan');
    expect($freshCalon->no_urut)->toBe(1);
});

test('rejecting candidate clears no_urut', function () {
    $user = User::factory()->create();
    $calon = createTestCandidate(['status' => 'ditetapkan', 'no_urut' => 1]);

    $response = $this->actingAs($user)->put("/dashboard/candidate/{$calon->id}", [
        'status' => 'ditolak',
    ]);

    $response->assertSessionHasNoErrors();

    $freshCalon = $calon->fresh();
    expect($freshCalon->status)->toBe('ditolak');
    expect($freshCalon->no_urut)->toBeNull();
});

test('can update no_urut for candidate already ditetapkan', function () {
    $user = User::factory()->create();
    $calon = createTestCandidate(['status' => 'ditetapkan', 'no_urut' => 1]);

    $response = $this->actingAs($user)->put("/dashboard/candidate/{$calon->id}", [
        'status' => 'ditetapkan',
        'no_urut' => 2,
    ]);

    $response->assertSessionHasNoErrors();

    $freshCalon = $calon->fresh();
    expect($freshCalon->no_urut)->toBe(2);
});
