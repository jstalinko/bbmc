<?php

use App\Models\Member;
use App\Models\Otp;
use App\Models\ElectionQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    Http::fake();
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

function createQueueMember(array $attributes = []) {
    return Member::create(array_merge([
        'nama_lengkap' => 'Test Member',
        'nama_panggilan' => 'Test',
        'tempat_lahir' => 'Bandung',
        'tanggal_lahir' => '01/01/1990',
        'jenis_kelamin' => 'L',
        'gol_darah' => 'O',
        'nik' => '1234567890123456',
        'alamat' => 'Jl. Test No. 1',
        'no_wa' => '628123456789',
        'status_keanggotaan' => 'LIFE MEMBER',
        'chapter' => 'Mother Chapter',
        'terdaftar_sejak' => '2010',
    ], $attributes));
}

test('queue is disabled by default (max_active_users <= 0)', function () {
    $member = createQueueMember(['no_kartu' => '0001']);
    
    Otp::create([
        'member_id' => $member->id,
        'otp' => '123456',
        'phone' => $member->no_wa,
        'expires_at' => now()->addMinutes(5),
        'is_verified' => false,
    ]);

    $response = $this->post('/election/login', [
        'no_kartu' => '0001',
        'otp' => '123456',
    ]);

    $response->assertRedirect('/election/dashboard');
    $this->assertEquals($member->id, session('election_member_id'));
    $this->assertNull(session('election_queue_member_id'));
    
    $this->assertDatabaseMissing('election_queues', ['member_id' => $member->id]);
});

test('queueStatus handles disabled queue', function () {
    $member = createQueueMember(['no_kartu' => '0001']);
    
    $response = $this->withSession(['election_queue_member_id' => $member->id])
        ->getJson('/election/queue-status');
        
    $response->assertStatus(200);
    $response->assertJson([
        'status' => 'active',
        'redirect' => route('election.dashboard'),
    ]);
    
    $this->assertEquals($member->id, session('election_member_id'));
    $this->assertNull(session('election_queue_member_id'));
});

test('queue enabled and slots available redirects to dashboard', function () {
    file_put_contents($this->settingPath, json_encode([
        'ajukan_diri' => true,
        'ajukan_anggota' => true,
        'tanggal_mulai' => null,
        'tanggal_selesai' => null,
        'max_active_users' => 5,
    ]));

    $member = createQueueMember(['no_kartu' => '0001']);
    
    Otp::create([
        'member_id' => $member->id,
        'otp' => '123456',
        'phone' => $member->no_wa,
        'expires_at' => now()->addMinutes(5),
        'is_verified' => false,
    ]);

    $response = $this->post('/election/login', [
        'no_kartu' => '0001',
        'otp' => '123456',
    ]);

    $response->assertRedirect('/election/dashboard');
    $this->assertEquals($member->id, session('election_member_id'));
    $this->assertNull(session('election_queue_member_id'));
    
    $this->assertDatabaseHas('election_queues', [
        'member_id' => $member->id,
        'status' => 'active',
    ]);
});

test('queue enabled and slots full redirects to queue page', function () {
    file_put_contents($this->settingPath, json_encode([
        'ajukan_diri' => true,
        'ajukan_anggota' => true,
        'tanggal_mulai' => null,
        'tanggal_selesai' => null,
        'max_active_users' => 1,
    ]));

    $activeMember = createQueueMember(['no_kartu' => '0001', 'nik' => '1234567890123457']);
    ElectionQueue::create([
        'member_id' => $activeMember->id,
        'status' => 'active',
        'last_activity' => now(),
    ]);

    $member = createQueueMember(['no_kartu' => '0002', 'nik' => '1234567890123458']);
    Otp::create([
        'member_id' => $member->id,
        'otp' => '123456',
        'phone' => $member->no_wa,
        'expires_at' => now()->addMinutes(5),
        'is_verified' => false,
    ]);

    $response = $this->post('/election/login', [
        'no_kartu' => '0002',
        'otp' => '123456',
    ]);

    $response->assertRedirect('/election/queue');
    $this->assertNull(session('election_member_id'));
    $this->assertEquals($member->id, session('election_queue_member_id'));
    
    $this->assertDatabaseHas('election_queues', [
        'member_id' => $member->id,
        'status' => 'waiting',
    ]);
});

test('cannot access queue page without queue session', function () {
    $response = $this->get('/election/queue');
    $response->assertRedirect('/election/login');
});

test('can access queue page with queue session', function () {
    $member = createQueueMember(['no_kartu' => '0001']);
    $response = $this->withSession(['election_queue_member_id' => $member->id])
        ->get('/election/queue');
    $response->assertStatus(200);
});

test('queue-status returns 401 if unauthorized', function () {
    $response = $this->getJson('/election/queue-status');
    $response->assertStatus(401);
    $response->assertJson(['status' => 'unauthorized']);
});

test('queue-status returns position if still waiting', function () {
    file_put_contents($this->settingPath, json_encode([
        'ajukan_diri' => true,
        'ajukan_anggota' => true,
        'tanggal_mulai' => null,
        'tanggal_selesai' => null,
        'max_active_users' => 1,
    ]));

    $activeMember = createQueueMember(['no_kartu' => '0001', 'nik' => '1234567890123401']);
    ElectionQueue::create([
        'member_id' => $activeMember->id,
        'status' => 'active',
        'last_activity' => now(),
    ]);

    // Freeze time for waiting member 1
    $time1 = now()->subSeconds(10);
    Illuminate\Support\Carbon::setTestNow($time1);
    $waitingMember1 = createQueueMember(['no_kartu' => '0002', 'nik' => '1234567890123402']);
    ElectionQueue::create([
        'member_id' => $waitingMember1->id,
        'status' => 'waiting',
        'last_activity' => now(),
    ]);

    // Freeze time for waiting member 2
    $time2 = now()->addSeconds(10);
    Illuminate\Support\Carbon::setTestNow($time2);
    $waitingMember2 = createQueueMember(['no_kartu' => '0003', 'nik' => '1234567890123403']);
    ElectionQueue::create([
        'member_id' => $waitingMember2->id,
        'status' => 'waiting',
        'last_activity' => now(),
    ]);

    // Reset Carbon test time
    Illuminate\Support\Carbon::setTestNow(null);

    $response = $this->withSession(['election_queue_member_id' => $waitingMember2->id])
        ->getJson('/election/queue-status');

    $response->assertStatus(200);
    $response->assertJson([
        'status' => 'waiting',
        'position' => 2,
    ]);
});

test('waiting member promoted to active when slot becomes available', function () {
    file_put_contents($this->settingPath, json_encode([
        'max_active_users' => 1,
    ]));

    $member = createQueueMember(['no_kartu' => '0001']);
    $queue = ElectionQueue::create([
        'member_id' => $member->id,
        'status' => 'waiting',
        'last_activity' => now(),
    ]);

    $response = $this->withSession(['election_queue_member_id' => $member->id])
        ->getJson('/election/queue-status');

    $response->assertStatus(200);
    $response->assertJson([
        'status' => 'active',
        'redirect' => route('election.dashboard'),
    ]);

    $this->assertEquals($member->id, session('election_member_id'));
    $this->assertNull(session('election_queue_member_id'));

    $this->assertDatabaseHas('election_queues', [
        'member_id' => $member->id,
        'status' => 'active',
    ]);
});

test('queue-status prunes inactive active and waiting sessions', function () {
    file_put_contents($this->settingPath, json_encode([
        'max_active_users' => 2,
    ]));

    $activeInactive = createQueueMember(['no_kartu' => '0001', 'nik' => '1234567890123411']);
    ElectionQueue::create([
        'member_id' => $activeInactive->id,
        'status' => 'active',
        'last_activity' => now()->subMinutes(6),
    ]);

    $activeActive = createQueueMember(['no_kartu' => '0002', 'nik' => '1234567890123412']);
    ElectionQueue::create([
        'member_id' => $activeActive->id,
        'status' => 'active',
        'last_activity' => now()->subMinutes(1),
    ]);

    $waitingInactive = createQueueMember(['no_kartu' => '0003', 'nik' => '1234567890123413']);
    ElectionQueue::create([
        'member_id' => $waitingInactive->id,
        'status' => 'waiting',
        'last_activity' => now()->subMinutes(3),
    ]);

    $member = createQueueMember(['no_kartu' => '0004', 'nik' => '1234567890123414']);
    ElectionQueue::create([
        'member_id' => $member->id,
        'status' => 'waiting',
        'last_activity' => now(),
    ]);

    $response = $this->withSession(['election_queue_member_id' => $member->id])
        ->getJson('/election/queue-status');

    $response->assertStatus(200);
    $response->assertJson([
        'status' => 'active',
        'redirect' => route('election.dashboard'),
    ]);

    $this->assertDatabaseMissing('election_queues', ['member_id' => $activeInactive->id]);
    $this->assertDatabaseHas('election_queues', ['member_id' => $activeActive->id]);
    $this->assertDatabaseMissing('election_queues', ['member_id' => $waitingInactive->id]);
});

test('queue-status returns expired if queue record was deleted', function () {
    file_put_contents($this->settingPath, json_encode([
        'max_active_users' => 1,
    ]));

    $member = createQueueMember(['no_kartu' => '0001']);

    $response = $this->withSession(['election_queue_member_id' => $member->id])
        ->getJson('/election/queue-status');

    $response->assertStatus(200);
    $response->assertJson(['status' => 'expired']);
});

test('ping endpoint updates last activity for active users', function () {
    $member = createQueueMember(['no_kartu' => '0001']);
    
    $queue = ElectionQueue::create([
        'member_id' => $member->id,
        'status' => 'active',
        'last_activity' => now()->subMinutes(3),
    ]);

    $response = $this->withSession(['election_member_id' => $member->id])
        ->postJson('/election/ping');

    $response->assertStatus(200);
    $response->assertJson(['success' => true]);

    $queue->refresh();
    $this->assertTrue($queue->last_activity->gt(now()->subSeconds(5)));
});

test('logout deletes active queue record', function () {
    $member = createQueueMember(['no_kartu' => '0001']);
    
    ElectionQueue::create([
        'member_id' => $member->id,
        'status' => 'active',
        'last_activity' => now(),
    ]);

    $response = $this->withSession(['election_member_id' => $member->id])
        ->post('/election/logout');

    $response->assertRedirect('/election/login');
    $this->assertNull(session('election_member_id'));
    
    $this->assertDatabaseMissing('election_queues', ['member_id' => $member->id]);
});
