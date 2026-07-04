<?php

use App\Models\User;
use App\Helper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->settingPath = storage_path('app/private/pemilihan-setting.json');
    $dir = dirname($this->settingPath);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    // Clear the file if it exists
    if (file_exists($this->settingPath)) {
        unlink($this->settingPath);
    }
});

afterEach(function () {
    if (file_exists($this->settingPath)) {
        unlink($this->settingPath);
    }
});

test('admin can save whatsapp piwapi credentials through settings', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/dashboard/setting-pemilihan', [
        'ajukan_diri' => true,
        'ajukan_anggota' => false,
        'tanggal_mulai' => '2026-07-04T12:00',
        'tanggal_selesai' => '2026-07-05T12:00',
        'piwapi_api_secret_key' => 'test-secret-key-123',
        'piwapi_account_id' => 'test-account-id-456',
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect();

    $this->assertFileExists($this->settingPath);
    $settings = json_decode(file_get_contents($this->settingPath), true);
    
    expect($settings['piwapi_api_secret_key'])->toBe('test-secret-key-123');
    expect($settings['piwapi_account_id'])->toBe('test-account-id-456');
});

test('Helper::sendWhatsapp retrieves credentials from pemilihan-setting.json', function () {
    Http::fake();

    // Write mock credentials to selection settings file
    file_put_contents($this->settingPath, json_encode([
        'ajukan_diri' => true,
        'ajukan_anggota' => true,
        'tanggal_mulai' => null,
        'tanggal_selesai' => null,
        'piwapi_api_secret_key' => 'json-secret-key',
        'piwapi_account_id' => 'json-account-id',
    ]));

    Helper::sendWhatsapp('08123456789', 'Hello Test');

    Http::assertSent(function ($request) {
        return $request->url() === 'https://piwapi.com/api/send/whatsapp'
            && $request['secret'] === 'json-secret-key'
            && $request['account'] === 'json-account-id'
            && $request['recipient'] === '628123456789';
    });
});
