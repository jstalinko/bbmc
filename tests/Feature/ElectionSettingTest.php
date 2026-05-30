<?php

use App\Models\User;
use App\Models\Member;
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
