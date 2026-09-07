<?php

use App\Helper;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->settingPath = storage_path('app/private/pemilihan-setting.json');
    if (file_exists($this->settingPath)) {
        unlink($this->settingPath);
    }
});

afterEach(function () {
    if (file_exists($this->settingPath)) {
        unlink($this->settingPath);
    }
});

test('Helper::sendWhatsapp sends via Piwapi when WHATSAPP_SERVICE is piwapi', function () {
    Http::fake([
        'https://piwapi.com/*' => Http::response(['status' => 200, 'message' => 'Success'], 200),
    ]);

    config([
        'services.whatsapp.service' => 'piwapi',
        'services.whatsapp.piwapi.api_secret_key' => 'mock-piwapi-secret',
        'services.whatsapp.piwapi.account_id' => 'mock-piwapi-account',
    ]);

    $res = Helper::sendWhatsapp('081234567890', 'Test message for Piwapi');

    Http::assertSent(function ($request) {
        return $request->url() === 'https://piwapi.com/api/send/whatsapp'
            && $request['secret'] === 'mock-piwapi-secret'
            && $request['account'] === 'mock-piwapi-account'
            && $request['recipient'] === '6281234567890'
            && $request['message'] === 'Test message for Piwapi';
    });

    expect($res['status'])->toBe(200);
});

test('Helper::sendWhatsapp sends OTP template via Bion when WHATSAPP_SERVICE is bion.id', function () {
    Http::fake([
        'https://crmapis2.bion.id/*' => Http::response([
            'messaging_product' => 'whatsapp',
            'contacts' => [['input' => '6281234567890', 'wa_id' => '6281234567890']],
            'messages' => [['id' => 'wamid.HBgM1234567890']]
        ], 200),
    ]);

    config([
        'services.whatsapp.service' => 'bion.id',
        'services.whatsapp.bion.api_url' => 'https://crmapis2.bion.id/api/meta',
        'services.whatsapp.bion.api_version' => 'v19.0',
        'services.whatsapp.bion.phone_number_id' => '115952861601111',
        'services.whatsapp.bion.access_token' => 'test-bion-bearer-token',
        'services.whatsapp.bion.auth_template_name' => 'authentication_template',
        'services.whatsapp.bion.template_language' => 'en',
    ]);

    $otpMessage = "*BBMC ELECTION 2026*\n\nKode OTP Anda untuk proses login portal adalah: *654321*\n\nBerlaku selama 5 menit.";
    $res = Helper::sendWhatsapp('081234567890', $otpMessage);

    Http::assertSent(function ($request) {
        $body = $request->data();
        return $request->url() === 'https://crmapis2.bion.id/api/meta/v19.0/115952861601111/messages'
            && $request->hasHeader('Authorization', 'Bearer test-bion-bearer-token')
            && $body['messaging_product'] === 'whatsapp'
            && $body['to'] === '6281234567890'
            && $body['type'] === 'template'
            && $body['template']['name'] === 'authentication_template'
            && $body['template']['language']['code'] === 'en'
            && $body['template']['components'][1]['parameters'][0]['text'] === '654321';
    });

    expect($res['success'])->toBeTrue();
    expect($res['status'])->toBe(200);
});

test('Helper::sendWhatsapp sends text message via Bion when type is explicitly text', function () {
    Http::fake([
        'https://crmapis2.bion.id/*' => Http::response([
            'messaging_product' => 'whatsapp',
            'contacts' => [['input' => '6281234567890', 'wa_id' => '6281234567890']],
            'messages' => [['id' => 'wamid.HBgM1234567890']]
        ], 200),
    ]);

    config([
        'services.whatsapp.service' => 'bion',
        'services.whatsapp.bion.api_url' => 'https://crmapis2.bion.id/api/meta',
        'services.whatsapp.bion.api_version' => 'v19.0',
        'services.whatsapp.bion.phone_number_id' => '115952861601111',
        'services.whatsapp.bion.access_token' => 'test-bion-bearer-token',
    ]);

    $res = Helper::sendWhatsapp('081234567890', 'Hello from Bion Text', ['type' => 'text']);

    Http::assertSent(function ($request) {
        $body = $request->data();
        return $request->url() === 'https://crmapis2.bion.id/api/meta/v19.0/115952861601111/messages'
            && $body['type'] === 'text'
            && $body['text']['body'] === 'Hello from Bion Text';
    });

    expect($res['success'])->toBeTrue();
    expect($res['status'])->toBe(200);
});

test('Helper::sendWhatsapp sends otp_bikers_mc template with queued response from Bion', function () {
    Http::fake([
        'https://crmapis2.bion.id/*' => Http::response([
            'messaging_channel' => 'whatsapp',
            'message' => [
                'queue_id' => 'test-queue-id-12345',
                'message_status' => 'queued',
            ],
        ], 200),
    ]);

    config([
        'services.whatsapp.service' => 'bion.id',
        'services.whatsapp.bion.api_url' => 'https://crmapis2.bion.id/api/meta',
        'services.whatsapp.bion.api_version' => 'v19.0',
        'services.whatsapp.bion.phone_number_id' => '115952861601111',
        'services.whatsapp.bion.access_token' => 'test-bion-bearer-token',
        'services.whatsapp.bion.auth_template_name' => 'otp_bikers_mc',
        'services.whatsapp.bion.template_language' => 'en_US',
    ]);

    $res = Helper::sendWhatsapp('081234567890', 'Hello brother 123456 is your otp', ['otp' => '123456']);

    Http::assertSent(function ($request) {
        $body = $request->data();
        return $request->url() === 'https://crmapis2.bion.id/api/meta/v19.0/115952861601111/messages'
            && $body['template']['name'] === 'otp_bikers_mc'
            && $body['template']['language']['code'] === 'en_US'
            && $body['template']['components'][0]['type'] === 'body'
            && $body['template']['components'][0]['parameters'][0]['text'] === 'Hello brother 123456 is your otp'
            && $body['template']['components'][1]['type'] === 'button'
            && $body['template']['components'][1]['sub_type'] === 'url'
            && $body['template']['components'][1]['parameters'][0]['text'] === '123456';
    });

    expect($res['success'])->toBeTrue();
    expect($res['status'])->toBe(200);
    expect($res['message']['queue_id'])->toBe('test-queue-id-12345');
});
