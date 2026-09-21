<?php

use App\Models\User;
use App\Models\Member;
use App\Models\WhatsappLog;
use App\Jobs\SendWhatsappJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

test('guests are redirected to login', function () {
    $response = $this->get('/dashboard/whatsapp');
    $response->assertRedirect('/login');
});

test('authenticated users can access whatsapp blast page', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get('/dashboard/whatsapp');
    $response->assertStatus(200);
});

test('it validates request parameters when sending with piwapi', function () {
    config(['services.whatsapp.service' => 'piwapi']);
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->postJson('/dashboard/whatsapp/send', [
        'member_ids' => [],
        'message' => '',
    ]);

    $response->assertStatus(422)
             ->assertJsonValidationErrors(['member_ids', 'message']);
});

test('it passes whatsapp_service and is_bion props to inertia view', function () {
    config(['services.whatsapp.service' => 'bion.id']);
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get('/dashboard/whatsapp');
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => 
        $page->component('Whatsapp/index', false)
             ->has('whatsapp_service')
             ->where('is_bion', true)
    );
});

test('it does not require message when whatsapp service is bion.id', function () {
    config(['services.whatsapp.service' => 'bion.id']);
    $user = User::factory()->create();
    $this->actingAs($user);

    $member = Member::create([
        'nama_lengkap' => 'Asep Bion',
        'nama_panggilan' => 'Asep',
        'tempat_lahir' => 'Bandung',
        'tanggal_lahir' => '12/03/1990',
        'jenis_kelamin' => 'L',
        'gol_darah' => 'O',
        'alamat' => 'Jl. Sudirman',
        'no_wa' => '628123456789',
        'status_keanggotaan' => 'LIFE MEMBER',
        'chapter' => 'Bandung'
    ]);

    Http::fake([
        'https://crmapis2.bion.id/*' => Http::response([
            'messaging_product' => 'whatsapp',
            'contacts' => [['input' => '628123456789', 'wa_id' => '628123456789']],
            'messages' => [['id' => 'wamid.123456']]
        ], 200),
    ]);

    $response = $this->postJson('/dashboard/whatsapp/send', [
        'member_ids' => [$member->id],
        // message is omitted / not sent
    ]);

    $response->assertStatus(200)
             ->assertJsonPath('queued', false)
             ->assertJsonPath('stats.progress', 100);

    $this->assertDatabaseHas('whatsapp_logs', [
        'recipient_name' => 'Asep Bion',
        'status' => 'success',
    ]);
});

test('it sends message synchronously when recipients count is 2 or less', function () {
    Http::fake([
        'piwapi.com/api/send/whatsapp' => Http::response([
            'status' => 200,
            'message' => 'WhatsApp chat has been queued for sending!',
            'data' => [
                'messageId' => '28577'
            ]
        ], 200)
    ]);

    $user = User::factory()->create();
    $this->actingAs($user);

    $member1 = Member::create([
        'nama_lengkap' => 'Asep Sunandar',
        'nama_panggilan' => 'Asep',
        'tempat_lahir' => 'Bandung',
        'tanggal_lahir' => '12/03/1990',
        'jenis_kelamin' => 'L',
        'gol_darah' => 'O',
        'alamat' => 'Jl. Sudirman',
        'no_wa' => '628123456789',
        'no_kartu' => '0001',
        'status_keanggotaan' => 'LIFE MEMBER',
        'chapter' => 'Bandung'
    ]);

    $member2 = Member::create([
        'nama_lengkap' => 'Budi Prasetyo',
        'nama_panggilan' => 'Budi',
        'tempat_lahir' => 'Jakarta',
        'tanggal_lahir' => '05/08/1992',
        'jenis_kelamin' => 'L',
        'gol_darah' => 'A',
        'alamat' => 'Jl. Merdeka',
        'no_wa' => '628987654321',
        'no_kartu' => '0002',
        'status_keanggotaan' => 'PROSPECT',
        'chapter' => 'Jakarta'
    ]);

    $response = $this->postJson('/dashboard/whatsapp/send', [
        'member_ids' => [$member1->id, $member2->id],
        'message' => 'Halo [[nama_lengkap]] dengan KTA [[no_kartu]], ini testing.',
    ]);

    $response->assertStatus(200)
             ->assertJsonPath('queued', false)
             ->assertJsonPath('stats.progress', 100);

    // Verify logs were created and set to success
    $this->assertDatabaseHas('whatsapp_logs', [
        'recipient_name' => 'Asep Sunandar',
        'recipient_phone' => '628123456789',
        'message' => 'Halo Asep Sunandar dengan KTA 0001, ini testing.',
        'status' => 'success'
    ]);

    $this->assertDatabaseHas('whatsapp_logs', [
        'recipient_name' => 'Budi Prasetyo',
        'recipient_phone' => '628987654321',
        'message' => 'Halo Budi Prasetyo dengan KTA 0002, ini testing.',
        'status' => 'success'
    ]);
});

test('it dispatches job to queue when recipients count is more than 2', function () {
    Queue::fake();

    $user = User::factory()->create();
    $this->actingAs($user);

    $member1 = Member::create([
        'nama_lengkap' => 'Asep Sunandar',
        'nama_panggilan' => 'Asep',
        'tempat_lahir' => 'Bandung',
        'tanggal_lahir' => '12/03/1990',
        'jenis_kelamin' => 'L',
        'gol_darah' => 'O',
        'alamat' => 'Jl. Sudirman',
        'no_wa' => '62812',
        'no_kartu' => '0001',
        'status_keanggotaan' => 'LIFE MEMBER',
        'chapter' => 'Bandung'
    ]);
    $member2 = Member::create([
        'nama_lengkap' => 'Budi Prasetyo',
        'nama_panggilan' => 'Budi',
        'tempat_lahir' => 'Jakarta',
        'tanggal_lahir' => '05/08/1992',
        'jenis_kelamin' => 'L',
        'gol_darah' => 'A',
        'alamat' => 'Jl. Merdeka',
        'no_wa' => '62898',
        'no_kartu' => '0002',
        'status_keanggotaan' => 'PROSPECT',
        'chapter' => 'Jakarta'
    ]);
    $member3 = Member::create([
        'nama_lengkap' => 'Cecep Hermawan',
        'nama_panggilan' => 'Cecep',
        'tempat_lahir' => 'Garut',
        'tanggal_lahir' => '10/10/1988',
        'jenis_kelamin' => 'L',
        'gol_darah' => 'B',
        'alamat' => 'Jl. Garut',
        'no_wa' => '62855',
        'no_kartu' => '0003',
        'status_keanggotaan' => 'HONORARY',
        'chapter' => 'Garut'
    ]);

    $response = $this->postJson('/dashboard/whatsapp/send', [
        'member_ids' => [$member1->id, $member2->id, $member3->id],
        'message' => 'Testing [[nama_lengkap]]',
    ]);

    $response->assertStatus(200)
             ->assertJsonPath('queued', true)
             ->assertJsonPath('stats.progress', 0);

    Queue::assertPushed(SendWhatsappJob::class, 3);

    // Verify logs were created but status remains pending
    $this->assertDatabaseHas('whatsapp_logs', [
        'recipient_name' => 'Asep Sunandar',
        'status' => 'pending'
    ]);
});

test('it returns status updates of a batch', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $batchId = 'blast_test123';

    WhatsappLog::create([
        'batch_id' => $batchId,
        'recipient_name' => 'Asep',
        'recipient_phone' => '62812',
        'message' => 'Hello Asep',
        'status' => 'success'
    ]);

    WhatsappLog::create([
        'batch_id' => $batchId,
        'recipient_name' => 'Budi',
        'recipient_phone' => '62898',
        'message' => 'Hello Budi',
        'status' => 'failed',
        'response' => '{"error": "API Limit exceeded"}'
    ]);

    $response = $this->getJson("/dashboard/whatsapp/status/{$batchId}");

    $response->assertStatus(200)
             ->assertJsonPath('stats.total', 2)
             ->assertJsonPath('stats.completed', 2)
             ->assertJsonPath('stats.success', 1)
             ->assertJsonPath('stats.failed', 1)
             ->assertJsonPath('stats.progress', 100);
});

test('it sends template message via bion matching console testing options', function () {
    config(['services.whatsapp.service' => 'bion.id']);
    $user = User::factory()->create();
    $this->actingAs($user);

    $member = Member::create([
        'nama_lengkap' => 'Alin Koko',
        'nama_panggilan' => 'Alin',
        'tempat_lahir' => 'Bandung',
        'tanggal_lahir' => '12/03/1990',
        'jenis_kelamin' => 'L',
        'gol_darah' => 'O',
        'alamat' => 'Jl. Sudirman',
        'no_wa' => '6287857580910',
        'no_kartu' => '0777',
        'status_keanggotaan' => 'LIFE MEMBER',
        'chapter' => 'Bandung'
    ]);

    Http::fake([
        'https://crmapis2.bion.id/*' => Http::response([
            'messaging_product' => 'whatsapp',
            'contacts' => [['input' => '6287857580910', 'wa_id' => '6287857580910']],
            'messages' => [['id' => 'wamid.blast123']]
        ], 200),
    ]);

    $response = $this->postJson('/dashboard/whatsapp/send', [
        'member_ids' => [$member->id],
    ]);

    $response->assertStatus(200)
             ->assertJsonPath('queued', false)
             ->assertJsonPath('stats.progress', 100);

    Http::assertSent(function ($request) {
        $body = $request->data();
        return $request->url() === 'https://crmapis2.bion.id/api/meta/v19.0/115952861601111/messages'
            && $body['type'] === 'template'
            && $body['template']['name'] === 'otp_bikers_mc'
            && isset($body['template']['components'][0]['parameters'][0]['text'])
            && preg_match('/^[0-9]{6}$/', $body['template']['components'][0]['parameters'][0]['text'])
            && isset($body['template']['components'][1]['parameters'][0]['text'])
            && preg_match('/^[0-9]{6}$/', $body['template']['components'][1]['parameters'][0]['text']);
    });

    $this->assertDatabaseHas('whatsapp_logs', [
        'recipient_name' => 'Alin Koko',
        'recipient_phone' => '6287857580910',
        'status' => 'success',
    ]);
});
