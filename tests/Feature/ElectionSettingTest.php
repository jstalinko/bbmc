<?php

use App\Models\User;
use App\Models\Member;
use App\Models\OfflineLog;
use App\Models\Polling;
use App\Models\Calon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->settingPath = storage_path('app/private/pemilihan-setting.json');
    $this->backupPath = storage_path('app/private/pemilihan-setting.json.bak');
    
    // Ensure directory exists
    $dir = dirname($this->settingPath);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    if (file_exists($this->settingPath)) {
        rename($this->settingPath, $this->backupPath);
    }
});

afterEach(function () {
    if (file_exists($this->settingPath)) {
        unlink($this->settingPath);
    }
    if (file_exists($this->backupPath)) {
        rename($this->backupPath, $this->settingPath);
    }
});

test('admin can save election settings', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->post('/dashboard/setting-pemilihan', [
        'ajukan_diri' => false,
        'ajukan_anggota' => false,
        'tanggal_mulai' => '2026-05-27T08:00',
        'tanggal_selesai' => '2026-05-30T18:00',
    ]);

    $response->assertRedirect();
    $this->assertFileExists($this->settingPath);
    
    $settings = json_decode(file_get_contents($this->settingPath), true);
    expect($settings['ajukan_diri'])->toBeFalse();
    expect($settings['ajukan_anggota'])->toBeFalse();
    expect($settings['tanggal_mulai'])->toBe('2026-05-27T08:00');
    expect($settings['tanggal_selesai'])->toBe('2026-05-30T18:00');
});

test('cannot nominate self when ajukan_diri is disabled', function () {
    file_put_contents($this->settingPath, json_encode([
        'ajukan_diri' => false,
        'ajukan_anggota' => true,
        'tanggal_mulai' => null,
        'tanggal_selesai' => null,
    ]));

    $response = $this->post('/election/nominate-self', [
        'no_kartu' => '0001',
        'otp' => '123456',
    ]);

    $response->assertSessionHasErrors(['otp']);
});

test('cannot nominate member when ajukan_anggota is disabled', function () {
    file_put_contents($this->settingPath, json_encode([
        'ajukan_diri' => true,
        'ajukan_anggota' => false,
        'tanggal_mulai' => null,
        'tanggal_selesai' => null,
    ]));

    $response = $this->post('/election/nominate-member', [
        'candidate_name' => 'Asep',
        'nominator_no_kartu' => '0001',
        'otp' => '123456',
    ]);

    $response->assertSessionHasErrors(['otp']);
});

test('cannot access login or polling before start date', function () {
    $futureDate = now()->addDays(2)->format('Y-m-d\TH:i');
    file_put_contents($this->settingPath, json_encode([
        'ajukan_diri' => true,
        'ajukan_anggota' => true,
        'tanggal_mulai' => $futureDate,
        'tanggal_selesai' => null,
    ]));

    // Try accessing login GET
    $response = $this->get('/election/login');
    $response->assertRedirect('/election/portal');
    $response->assertSessionHas('error');

    // Try accessing polling GET
    $response = $this->get('/election/polling');
    $response->assertRedirect('/election/portal');
    $response->assertSessionHas('error');
});

test('cannot access login after end date', function () {
    $pastDate = now()->subDays(2)->format('Y-m-d\TH:i');
    file_put_contents($this->settingPath, json_encode([
        'ajukan_diri' => true,
        'ajukan_anggota' => true,
        'tanggal_mulai' => null,
        'tanggal_selesai' => $pastDate,
    ]));

    // Try accessing login GET
    $response = $this->get('/election/login');
    $response->assertRedirect('/election/portal');
    $response->assertSessionHas('error');
});

test('admin can save and update tampilkan_kandidat_polling setting', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->post('/dashboard/setting-pemilihan', [
        'ajukan_diri' => true,
        'ajukan_anggota' => true,
        'tampilkan_kandidat_polling' => false,
    ]);

    $response->assertRedirect();
    $this->assertFileExists($this->settingPath);

    $settings = json_decode(file_get_contents($this->settingPath), true);
    expect($settings['tampilkan_kandidat_polling'])->toBeFalse();

    $response2 = $this->post('/dashboard/setting-pemilihan', [
        'ajukan_diri' => true,
        'ajukan_anggota' => true,
        'tampilkan_kandidat_polling' => true,
    ]);

    $response2->assertRedirect();
    $settings2 = json_decode(file_get_contents($this->settingPath), true);
    expect($settings2['tampilkan_kandidat_polling'])->toBeTrue();
});

function createElectionSettingMember(array $attributes = []) {
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

test('polling page displays correct statistics for offline and online votes and voters', function () {
    \Illuminate\Support\Facades\Cache::flush();

    $member1 = createElectionSettingMember([
        'nama_lengkap' => 'Member One',
        'no_kartu' => '0001',
        'penalty' => 'clean',
        'offline_voter' => true,
    ]);
    $member2 = createElectionSettingMember([
        'nama_lengkap' => 'Member Two',
        'no_kartu' => '0002',
        'penalty' => 'clean',
        'offline_voter' => true,
    ]);
    $member3 = createElectionSettingMember([
        'nama_lengkap' => 'Member Three',
        'no_kartu' => '0003',
        'penalty' => 'clean',
        'offline_voter' => false,
    ]);
    $member4 = createElectionSettingMember([
        'nama_lengkap' => 'Member Four',
        'no_kartu' => '0004',
        'penalty' => 'clean',
        'offline_voter' => false,
    ]);
    $member5 = createElectionSettingMember([
        'nama_lengkap' => 'Member Five',
        'no_kartu' => '0005',
        'penalty' => 'clean',
        'offline_voter' => false,
    ]);
    createElectionSettingMember([
        'nama_lengkap' => 'Member Banned',
        'no_kartu' => '9999',
        'penalty' => 'banned',
        'offline_voter' => false,
    ]);

    OfflineLog::create([
        'nama_pengurus' => 'Pengurus 1',
        'kode_akses' => 'CODE1',
        'member_id' => $member1->id,
        'no_antrian' => 1,
        'voting_status' => OfflineLog::STATUS_SUDAH_MEMILIH,
    ]);
    OfflineLog::create([
        'nama_pengurus' => 'Pengurus 1',
        'kode_akses' => 'CODE1',
        'member_id' => $member2->id,
        'no_antrian' => 2,
        'voting_status' => OfflineLog::STATUS_ANTREAN,
    ]);

    $calon = Calon::create([
        'member_id' => $member3->id,
        'no_kartu' => '0003',
        'chapter' => 'Mother Chapter',
        'status' => 'ditetapkan',
        'no_urut' => 1,
    ]);

    Polling::create([
        'member_id' => $member4->id,
        'calon_id' => $calon->id,
    ]);

    file_put_contents($this->settingPath, json_encode([
        'ajukan_diri' => true,
        'ajukan_anggota' => true,
        'tampilkan_kandidat_polling' => true,
        'tanggal_mulai' => null,
        'tanggal_selesai' => null,
    ]));

    $response = $this->get('/election/polling');
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => 
        $page->component('Election/polling', false)
             ->where('tampilkanKandidat', true)
             ->where('totalPemilih', 5)
             ->where('totalPemilihOffline', 2)
             ->where('totalPemilihOnline', 3)
             ->where('totalSuaraOffline', 2)
             ->where('totalSuaraOnline', 1)
             ->where('totalSuaraMasuk', 3)
             ->where('partisipasiSuara', 60)
             ->has('results', 1)
    );
});

test('polling page hides candidate details when tampilkan_kandidat_polling is disabled', function () {
    \Illuminate\Support\Facades\Cache::flush();

    $member = createElectionSettingMember([
        'nama_lengkap' => 'Member One',
        'no_kartu' => '0001',
        'penalty' => 'clean',
        'offline_voter' => false,
    ]);

    Calon::create([
        'member_id' => $member->id,
        'no_kartu' => '0001',
        'chapter' => 'Mother Chapter',
        'status' => 'ditetapkan',
        'no_urut' => 1,
    ]);

    file_put_contents($this->settingPath, json_encode([
        'ajukan_diri' => true,
        'ajukan_anggota' => true,
        'tampilkan_kandidat_polling' => false,
        'tanggal_mulai' => null,
        'tanggal_selesai' => null,
    ]));

    $response = $this->get('/election/polling');
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => 
        $page->component('Election/polling', false)
             ->where('tampilkanKandidat', false)
             ->where('results', [])
    );
});

