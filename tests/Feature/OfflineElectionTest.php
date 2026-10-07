<?php

use App\Models\Member;
use App\Models\User;
use App\Models\Polling;
use App\Models\Calon;
use App\Models\Otp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    Http::fake();
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
    ]));
});

afterEach(function () {
    if (file_exists($this->settingPath)) {
        unlink($this->settingPath);
    }
});

function createOfflineTestMember(array $attributes = []) {
    return Member::create(array_merge([
        'nama_lengkap' => 'Budi Santoso',
        'nama_panggilan' => 'Budi',
        'tempat_lahir' => 'Bandung',
        'tanggal_lahir' => '01/01/1990',
        'jenis_kelamin' => 'L',
        'gol_darah' => 'O',
        'nik' => '1234567890123456',
        'alamat' => 'Jl. Test No. 1',
        'no_wa' => '628123456789',
        'no_kartu' => '0023',
        'status_keanggotaan' => 'LIFE MEMBER',
        'chapter' => 'Mother Chapter',
        'terdaftar_sejak' => '2010',
        'penalty' => 'clean',
        'offline_voter' => false,
    ], $attributes));
}

test('guest cannot access /pemilihan-offline', function () {
    $response = $this->get('/pemilihan-offline');
    $response->assertRedirect('/login');
});

test('admin can access /pemilihan-offline and /dashboard/pemilihan-offline', function () {
    $admin = User::factory()->create();
    
    $response = $this->actingAs($admin)->get('/pemilihan-offline');
    $response->assertStatus(200);

    $response2 = $this->actingAs($admin)->get('/dashboard/pemilihan-offline');
    $response2->assertStatus(200);
});

test('admin can search members on /pemilihan-offline by name and no_kartu', function () {
    $admin = User::factory()->create();
    createOfflineTestMember(['nama_lengkap' => 'Cecep Motor', 'no_kartu' => '0050']);
    createOfflineTestMember(['nama_lengkap' => 'Doni Rider', 'no_kartu' => '0060']);

    $response = $this->actingAs($admin)->get('/pemilihan-offline?search=Cecep');
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => 
        $page->component('Election/Offline', false)
             ->has('members.data', 1)
             ->where('members.data.0.nama_lengkap', 'Cecep Motor')
    );

    $responseKta = $this->actingAs($admin)->get('/pemilihan-offline?search=60');
    $responseKta->assertStatus(200);
    $responseKta->assertInertia(fn ($page) => 
        $page->component('Election/Offline', false)
             ->has('members.data', 1)
             ->where('members.data.0.nama_lengkap', 'Doni Rider')
    );
});

test('admin can mark a member as offline_voter', function () {
    $admin = User::factory()->create();
    $member = createOfflineTestMember(['no_kartu' => '0010', 'offline_voter' => false]);

    $response = $this->actingAs($admin)->post("/pemilihan-offline/{$member->id}/mark");
    $response->assertSessionHas('success');

    $member->refresh();
    expect($member->offline_voter)->toBeTrue();
});

test('admin can unmark an offline_voter member', function () {
    $admin = User::factory()->create();
    $member = createOfflineTestMember(['no_kartu' => '0010', 'offline_voter' => true]);

    $response = $this->actingAs($admin)->post("/pemilihan-offline/{$member->id}/unmark");
    $response->assertSessionHas('success');

    $member->refresh();
    expect($member->offline_voter)->toBeFalse();
});

test('member marked as offline_voter cannot send login otp online', function () {
    $member = createOfflineTestMember(['no_kartu' => '0025', 'offline_voter' => true]);

    $response = $this->postJson('/api/send-login-otp', [
        'no_kartu' => '0025',
    ]);

    $response->assertStatus(403);
    $response->assertJson([
        'success' => false,
        'offline_voter' => true,
    ]);
});

test('member marked as offline_voter cannot login online with otp', function () {
    $member = createOfflineTestMember(['no_kartu' => '0025', 'offline_voter' => true]);
    
    Otp::create([
        'member_id' => $member->id,
        'otp' => '123456',
        'phone' => $member->no_wa,
        'expires_at' => now()->addMinutes(5),
        'is_verified' => false,
    ]);

    $response = $this->post('/election/login', [
        'no_kartu' => '0025',
        'otp' => '123456',
    ]);

    $response->assertSessionHasErrors('no_kartu');
    $this->assertNull(session('election_member_id'));
});

test('authenticated session is invalidated by ElectionAuth if member is marked offline_voter', function () {
    $member = createOfflineTestMember(['no_kartu' => '0025', 'offline_voter' => true]);

    $response = $this->withSession(['election_member_id' => $member->id])
        ->get('/election/dashboard');

    $response->assertRedirect('/election/login');
    $response->assertSessionHasErrors('no_kartu');
});

test('member who has voted online cannot be marked as offline voter', function () {
    $admin = User::factory()->create();
    $member = createOfflineTestMember(['no_kartu' => '0030', 'offline_voter' => false]);
    
    // Create candidate & polling
    $candidateMember = createOfflineTestMember(['no_kartu' => '0031']);
    $calon = Calon::create([
        'member_id' => $candidateMember->id,
        'no_kartu' => '0031',
        'chapter' => 'Mother Chapter',
        'status' => 'ditetapkan',
        'diajukan_oleh' => 'self',
    ]);

    Polling::create([
        'member_id' => $member->id,
        'calon_id' => $calon->id,
    ]);

    $response = $this->actingAs($admin)->post("/pemilihan-offline/{$member->id}/mark");
    $response->assertSessionHasErrors('error');

    $member->refresh();
    expect($member->offline_voter)->toBeFalse();
});
