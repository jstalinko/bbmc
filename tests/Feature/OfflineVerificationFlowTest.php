<?php

use App\Models\Member;
use App\Models\OfflineLog;
use App\Models\Polling;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->settingPath = storage_path('app/private/pemilihan-setting.json');
    $dir = dirname($this->settingPath);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    file_put_contents($this->settingPath, json_encode([
        'ajukan_diri' => true,
        'ajukan_anggota' => true,
        'tanggal_mulai' => null,
        'tanggal_selesai' => null,
        'kode_akses_pengurus' => [
            [
                'kode_akses' => 'PANITIA-01',
                'nama_pengurus' => 'Kang Dicky',
            ],
            [
                'kode_akses' => 'PANITIA-02',
                'nama_pengurus' => 'Kang Asep',
            ],
        ],
    ]));
});

afterEach(function () {
    if (file_exists($this->settingPath)) {
        unlink($this->settingPath);
    }
});

function createVerifyFlowMember(array $attributes = []) {
    return Member::create(array_merge([
        'nama_lengkap' => 'Budi Santoso',
        'nama_panggilan' => 'Budi',
        'tempat_lahir' => 'Bandung',
        'tanggal_lahir' => '01/01/1985',
        'jenis_kelamin' => 'L',
        'gol_darah' => 'O',
        'nik' => '1234567890123456',
        'alamat' => 'Jl. Diponegoro No. 1',
        'no_wa' => '628123456789',
        'no_kartu' => '0123',
        'status_keanggotaan' => 'LIFE MEMBER',
        'chapter' => 'Mother Chapter',
        'terdaftar_sejak' => '2010',
        'penalty' => 'clean',
        'offline_voter' => false,
    ], $attributes));
}

test('dashboard setting can save and update kode_akses_pengurus', function () {
    $admin = User::factory()->create();

    $response = $this->actingAs($admin)->post('/dashboard/setting-pemilihan', [
        'ajukan_diri' => true,
        'ajukan_anggota' => true,
        'max_active_users' => 0,
        'kode_akses_pengurus' => [
            ['kode_akses' => 'TPS-A1', 'nama_pengurus' => 'Panitia TPS 1'],
            ['kode_akses' => 'TPS-B1', 'nama_pengurus' => 'Panitia TPS 2'],
        ],
    ]);

    $response->assertSessionHas('success');

    $saved = json_decode(file_get_contents($this->settingPath), true);
    expect($saved['kode_akses_pengurus'])->toHaveCount(2);
    expect($saved['kode_akses_pengurus'][0]['kode_akses'])->toBe('TPS-A1');
    expect($saved['kode_akses_pengurus'][0]['nama_pengurus'])->toBe('Panitia TPS 1');
});

test('first time access /offline/verify shows login gate without pengurus session', function () {
    $response = $this->get('/offline/verify');
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Offline/Verify', false)
        ->where('current_pengurus', null)
    );
});

test('pengurus login with invalid access code fails', function () {
    $response = $this->post('/offline/login', [
        'kode_akses' => 'SALAH-KODE',
    ]);

    $response->assertSessionHasErrors('kode_akses');
    $this->assertNull(session('offline_pengurus_kode'));
});

test('pengurus login with valid access code succeeds and establishes session', function () {
    $response = $this->post('/offline/login', [
        'kode_akses' => 'PANITIA-01',
    ]);

    $response->assertRedirect('/offline/verify');
    expect(session('offline_pengurus_kode'))->toBe('PANITIA-01');
    expect(session('offline_pengurus_nama'))->toBe('Kang Dicky');

    $verifyResponse = $this->get('/offline/verify');
    $verifyResponse->assertInertia(fn ($page) => $page
        ->component('Offline/Verify', false)
        ->where('current_pengurus.kode_akses', 'PANITIA-01')
        ->where('current_pengurus.nama_pengurus', 'Kang Dicky')
    );
});

test('pengurus can logout from /offline/verify', function () {
    $this->withSession([
        'offline_pengurus_kode' => 'PANITIA-01',
        'offline_pengurus_nama' => 'Kang Dicky',
    ])->post('/offline/logout')
      ->assertRedirect('/offline/verify');

    $this->assertNull(session('offline_pengurus_kode'));
});

test('verify rejects if no_antrian is missing or duplicate', function () {
    $member = createVerifyFlowMember(['no_kartu' => '0123']);
    OfflineLog::create([
        'nama_pengurus' => 'Kang Dicky',
        'kode_akses' => 'PANITIA-01',
        'member_id' => $member->id,
        'no_antrian' => 5,
        'voting_status' => OfflineLog::STATUS_ANTREAN,
    ]);

    $member2 = createVerifyFlowMember(['no_kartu' => '0124', 'no_wa' => '628999999999']);

    // Missing no_antrian
    $resMissing = $this->withSession([
        'offline_pengurus_kode' => 'PANITIA-01',
        'offline_pengurus_nama' => 'Kang Dicky',
    ])->post('/offline/verify', [
        'no_kartu' => '0124',
    ]);
    $resMissing->assertSessionHasErrors('no_antrian');

    // Duplicate no_antrian
    $resDuplicate = $this->withSession([
        'offline_pengurus_kode' => 'PANITIA-01',
        'offline_pengurus_nama' => 'Kang Dicky',
    ])->post('/offline/verify', [
        'no_kartu' => '0124',
        'no_antrian' => 5,
    ]);
    $resDuplicate->assertSessionHasErrors('no_antrian');
});

test('verify rejects card if member does not exist', function () {
    $response = $this->withSession([
        'offline_pengurus_kode' => 'PANITIA-01',
        'offline_pengurus_nama' => 'Kang Dicky',
    ])->post('/offline/verify', [
        'no_kartu' => '9999',
        'no_antrian' => 1,
    ]);

    $response->assertSessionHasErrors('no_kartu');
});

test('verify rejects card if member is not LIFE MEMBER or SS DIPONEGORO', function () {
    createVerifyFlowMember([
        'no_kartu' => '0555',
        'status_keanggotaan' => 'VIRGIN',
    ]);

    $response = $this->withSession([
        'offline_pengurus_kode' => 'PANITIA-01',
        'offline_pengurus_nama' => 'Kang Dicky',
    ])->post('/offline/verify', [
        'no_kartu' => '0555',
        'no_antrian' => 1,
    ]);

    $response->assertSessionHasErrors('no_kartu');
});

test('verify rejects card if member has active penalty', function () {
    createVerifyFlowMember([
        'no_kartu' => '0666',
        'penalty' => 'warning',
        'penalty_reason' => 'Indisipliner',
    ]);

    $response = $this->withSession([
        'offline_pengurus_kode' => 'PANITIA-01',
        'offline_pengurus_nama' => 'Kang Dicky',
    ])->post('/offline/verify', [
        'no_kartu' => '0666',
        'no_antrian' => 1,
    ]);

    $response->assertSessionHasErrors('no_kartu');
});

test('verify rejects card if member already voted online', function () {
    $member = createVerifyFlowMember(['no_kartu' => '0777']);
    Polling::create([
        'member_id' => $member->id,
        'calon_id' => 1,
    ]);

    $response = $this->withSession([
        'offline_pengurus_kode' => 'PANITIA-01',
        'offline_pengurus_nama' => 'Kang Dicky',
    ])->post('/offline/verify', [
        'no_kartu' => '0777',
        'no_antrian' => 1,
    ]);

    $response->assertSessionHasErrors('no_kartu');
});

test('verify rejects card if member already registered in offline_logs', function () {
    $member = createVerifyFlowMember(['no_kartu' => '0888']);
    OfflineLog::create([
        'nama_pengurus' => 'Kang Dicky',
        'kode_akses' => 'PANITIA-01',
        'member_id' => $member->id,
        'no_antrian' => 1,
        'voting_status' => OfflineLog::STATUS_ANTREAN,
    ]);

    $response = $this->withSession([
        'offline_pengurus_kode' => 'PANITIA-01',
        'offline_pengurus_nama' => 'Kang Dicky',
    ])->post('/offline/verify', [
        'no_kartu' => '0888',
        'no_antrian' => 2,
    ]);

    $response->assertSessionHasErrors('no_kartu');
});

test('verify successfully registers eligible member into offline_logs and marks offline_voter', function () {
    $member1 = createVerifyFlowMember([
        'nama_lengkap' => 'Asep Kurniawan',
        'no_kartu' => '0111',
        'no_wa' => '628111111111',
    ]);

    $member2 = createVerifyFlowMember([
        'nama_lengkap' => 'Dadan Ramdani',
        'no_kartu' => '0222',
        'no_wa' => '628222222222',
    ]);

    // Verify first member with manual no_antrian
    $response1 = $this->withSession([
        'offline_pengurus_kode' => 'PANITIA-01',
        'offline_pengurus_nama' => 'Kang Dicky',
    ])->post('/offline/verify', [
        'no_kartu' => '0111',
        'no_antrian' => 10,
        'no_tps' => 'TPS 1',
    ]);

    $response1->assertSessionHas('success');
    $member1->refresh();
    expect($member1->offline_voter)->toBeTrue();

    $log1 = OfflineLog::where('member_id', $member1->id)->first();
    expect($log1)->not->toBeNull();
    expect($log1->no_antrian)->toBe(10);
    expect($log1->nama_pengurus)->toBe('Kang Dicky');
    expect($log1->kode_akses)->toBe('PANITIA-01');
    expect($log1->no_tps)->toBe('TPS 1');
    expect($log1->voting_status)->toBe(OfflineLog::STATUS_ANTREAN);

    // Verify second member with manual no_antrian
    $response2 = $this->withSession([
        'offline_pengurus_kode' => 'PANITIA-02',
        'offline_pengurus_nama' => 'Kang Asep',
    ])->post('/offline/verify', [
        'no_kartu' => '0222',
        'no_antrian' => 11,
    ]);

    $response2->assertSessionHas('success');
    $member2->refresh();
    expect($member2->offline_voter)->toBeTrue();

    $log2 = OfflineLog::where('member_id', $member2->id)->first();
    expect($log2)->not->toBeNull();
    expect($log2->no_antrian)->toBe(11);
    expect($log2->nama_pengurus)->toBe('Kang Asep');
});

test('pengurus can update offline log voting_status', function () {
    $member = createVerifyFlowMember(['no_kartu' => '0333']);
    $log = OfflineLog::create([
        'nama_pengurus' => 'Kang Dicky',
        'kode_akses' => 'PANITIA-01',
        'member_id' => $member->id,
        'no_antrian' => 1,
        'voting_status' => OfflineLog::STATUS_ANTREAN,
    ]);

    // Mark as sudah_memilih
    $response = $this->withSession([
        'offline_pengurus_kode' => 'PANITIA-01',
        'offline_pengurus_nama' => 'Kang Dicky',
    ])->post("/offline/queue/{$log->id}/status", [
        'voting_status' => OfflineLog::STATUS_SUDAH_MEMILIH,
    ]);

    $response->assertSessionHas('success');
    $log->refresh();
    expect($log->voting_status)->toBe(OfflineLog::STATUS_SUDAH_MEMILIH);

    // Mark as tidak_memilih
    $this->withSession([
        'offline_pengurus_kode' => 'PANITIA-01',
        'offline_pengurus_nama' => 'Kang Dicky',
    ])->post("/offline/queue/{$log->id}/status", [
        'voting_status' => OfflineLog::STATUS_TIDAK_MEMILIH,
    ]);

    $log->refresh();
    expect($log->voting_status)->toBe(OfflineLog::STATUS_TIDAK_MEMILIH);
});
