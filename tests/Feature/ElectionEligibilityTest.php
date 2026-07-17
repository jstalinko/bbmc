<?php

use App\Models\Member;
use App\Models\Otp;
use App\Models\Polling;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    Http::fake();
    $this->settingPath = storage_path('app/private/pemilihan-setting.json');
    
    // Ensure election window is active/open by default
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

// Helper function to create member
function createTestMember(array$attributes = []) {
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

/*
|--------------------------------------------------------------------------
| Login Eligibility Tests
|--------------------------------------------------------------------------
*/

test('members with LIFE MEMBER status can get login OTP', function () {
    createTestMember([
        'no_kartu' => '0001',
        'status_keanggotaan' => 'LIFE MEMBER',
        'no_wa' => '628123456789',
    ]);

    $response = $this->postJson('/api/send-login-otp', [
        'no_kartu' => '0001',
    ]);

    $response->assertStatus(200);
    $response->assertJsonPath('success', true);
});

test('members with SS DIPONEGORO status can get login OTP', function () {
    createTestMember([
        'no_kartu' => '0002',
        'status_keanggotaan' => 'SS DIPONEGORO',
        'no_wa' => '628123456789',
        'nik' => '1234567890123457',
    ]);

    $response = $this->postJson('/api/send-login-otp', [
        'no_kartu' => '0002',
    ]);

    $response->assertStatus(200);
    $response->assertJsonPath('success', true);
});

test('members with other statuses cannot get login OTP', function () {
    createTestMember([
        'no_kartu' => '0003',
        'status_keanggotaan' => 'PROSPECT',
        'no_wa' => '628123456789',
        'nik' => '1234567890123458',
    ]);

    $response = $this->postJson('/api/send-login-otp', [
        'no_kartu' => '0003',
    ]);

    $response->assertStatus(403);
    $response->assertJsonPath('success', false);
    $response->assertJsonPath('message', 'Hanya anggota dengan status LIFE MEMBER atau SS DIPONEGORO yang dapat masuk.');
});

test('members with LIFE MEMBER status can log in successfully', function () {
    $member = createTestMember([
        'no_kartu' => '0001',
        'status_keanggotaan' => 'LIFE MEMBER',
    ]);

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
});

test('members with other statuses cannot log in', function () {
    $member = createTestMember([
        'no_kartu' => '0003',
        'status_keanggotaan' => 'PROSPECT',
        'nik' => '1234567890123459',
    ]);

    Otp::create([
        'member_id' => $member->id,
        'otp' => '123456',
        'phone' => $member->no_wa,
        'expires_at' => now()->addMinutes(5),
        'is_verified' => false,
    ]);

    $response = $this->post('/election/login', [
        'no_kartu' => '0003',
        'otp' => '123456',
    ]);

    $response->assertSessionHasErrors(['no_kartu']);
    $this->assertNull(session('election_member_id'));
});

/*
|--------------------------------------------------------------------------
| Nomination Autocomplete & Info Endpoint Tests
|--------------------------------------------------------------------------
*/

test('search-members only returns LIFE MEMBERs registered for at least 10 years', function () {
    $currentYear = intval(date('Y'));
    
    // Eligible member (LIFE MEMBER, registered 11 years ago)
    $m1 = createTestMember([
        'nama_lengkap' => 'Ahmad Fauzi',
        'status_keanggotaan' => 'LIFE MEMBER',
        'terdaftar_sejak' => (string)($currentYear - 11),
        'nik' => '1234567890123410',
        'no_kartu' => '0020',
    ]);

    // Ineligible status (SS DIPONEGORO, registered 11 years ago)
    $m2 = createTestMember([
        'nama_lengkap' => 'Budi Santoso',
        'status_keanggotaan' => 'SS DIPONEGORO',
        'terdaftar_sejak' => (string)($currentYear - 11),
        'nik' => '1234567890123411',
        'no_kartu' => '0021',
    ]);

    // Ineligible duration (LIFE MEMBER, registered 5 years ago)
    $m3 = createTestMember([
        'nama_lengkap' => 'Ahmad Sudirman',
        'status_keanggotaan' => 'LIFE MEMBER',
        'terdaftar_sejak' => (string)($currentYear - 5),
        'nik' => '1234567890123412',
        'no_kartu' => '0022',
    ]);

    // Test searching 'Ahmad'
    $response = $this->getJson('/election/search-members?q=Ahmad');
    $response->assertStatus(200);
    
    $results = $response->json();
    $this->assertCount(1, $results);
    $this->assertEquals('Ahmad Fauzi', $results[0]['nama_lengkap']);
});

test('search-members still returns candidates who are already nominated and prioritizes name over chapter matches', function () {
    $currentYear = intval(date('Y'));
    
    // Create member whose name is Jaka (Mother Chapter)
    $jaka = createTestMember([
        'nama_lengkap' => 'Jaka Pratama',
        'nama_panggilan' => 'Jaka',
        'chapter' => 'Mother Chapter',
        'status_keanggotaan' => 'LIFE MEMBER',
        'terdaftar_sejak' => (string)($currentYear - 15),
        'nik' => '1234567890123501',
        'no_kartu' => '0501',
    ]);

    // Create member from Jakarta Chapter who does not have jaka in their name
    $jakartaMember = createTestMember([
        'nama_lengkap' => 'Samiaji Brokenbones',
        'nama_panggilan' => 'Aji',
        'chapter' => 'Jakarta Chapter',
        'status_keanggotaan' => 'LIFE MEMBER',
        'terdaftar_sejak' => (string)($currentYear - 15),
        'nik' => '1234567890123502',
        'no_kartu' => '0502',
    ]);

    // Jaka is ALREADY in calons table (nominated by 1001)
    \App\Models\Calon::create([
        'member_id' => $jaka->id,
        'no_kartu' => $jaka->no_kartu,
        'chapter' => $jaka->chapter,
        'status' => 'diajukan',
        'diajukan_oleh' => 'Benny Gumilar',
        'no_kartu_diajukan_oleh' => '1001',
    ]);

    // Search for 'jaka'
    $response = $this->getJson('/election/search-members?q=jaka');
    $response->assertStatus(200);
    
    $results = $response->json();
    $this->assertGreaterThanOrEqual(2, count($results));
    
    // Jaka Pratama (exact name match) must be first, before Samiaji (who only matched because of Jakarta Chapter)
    $this->assertEquals('Jaka Pratama', $results[0]['nama_lengkap']);
    $this->assertTrue(collect($results)->pluck('nama_lengkap')->contains('Jaka Pratama'));
});

test('member-info fails for non-LIFE MEMBER', function () {
    createTestMember([
        'no_kartu' => '0004',
        'status_keanggotaan' => 'PROSPECT',
        'terdaftar_sejak' => '2010',
        'nik' => '1234567890123413',
    ]);

    $response = $this->getJson('/election/member-info/0004');
    $response->assertStatus(200);
    $response->assertJsonPath('success', false);
    $response->assertJsonPath('message', 'Hanya anggota dengan status LIFE MEMBER atau SS DIPONEGORO yang dapat berpartisipasi dalam pencalonan/pengajuan.');
});

test('member-info fails for LIFE MEMBER registered less than 10 years ago', function () {
    $currentYear = intval(date('Y'));
    createTestMember([
        'no_kartu' => '0005',
        'status_keanggotaan' => 'LIFE MEMBER',
        'terdaftar_sejak' => (string)($currentYear - 9),
        'nik' => '1234567890123414',
    ]);

    $response = $this->getJson('/election/member-info/0005');
    $response->assertStatus(200);
    $response->assertJsonPath('success', false);
    $response->assertJsonPath('message', 'Hanya anggota dengan masa keanggotaan minimal 10 tahun yang dapat berpartisipasi dalam pencalonan/pengajuan.');
});

test('member-info succeeds for eligible member', function () {
    $currentYear = intval(date('Y'));
    $member = createTestMember([
        'no_kartu' => '0006',
        'status_keanggotaan' => 'LIFE MEMBER',
        'terdaftar_sejak' => (string)($currentYear - 10),
        'nik' => '1234567890123415',
    ]);

    $response = $this->getJson('/election/member-info/0006');
    $response->assertStatus(200);
    $response->assertJsonPath('success', true);
    $response->assertJsonPath('member.nama_lengkap', $member->nama_lengkap);
});

/*
|--------------------------------------------------------------------------
| Nomination Submit Actions Tests
|--------------------------------------------------------------------------
*/

test('nominateSelf fails for ineligible member', function () {
    $currentYear = intval(date('Y'));
    $member = createTestMember([
        'no_kartu' => '0007',
        'status_keanggotaan' => 'LIFE MEMBER',
        'terdaftar_sejak' => (string)($currentYear - 5),
        'nik' => '1234567890123416',
    ]);

    Otp::create([
        'member_id' => $member->id,
        'otp' => '123456',
        'phone' => $member->no_wa,
        'expires_at' => now()->addMinutes(5),
        'is_verified' => false,
    ]);

    $response = $this->post('/election/nominate-self', [
        'no_kartu' => '0007',
        'otp' => '123456',
    ]);

    $response->assertSessionHasErrors(['no_kartu']);
});

test('nominateSelf succeeds for eligible member', function () {
    $currentYear = intval(date('Y'));
    $member = createTestMember([
        'no_kartu' => '0008',
        'status_keanggotaan' => 'LIFE MEMBER',
        'terdaftar_sejak' => (string)($currentYear - 12),
        'nik' => '1234567890123417',
    ]);

    Otp::create([
        'member_id' => $member->id,
        'otp' => '123456',
        'phone' => $member->no_wa,
        'expires_at' => now()->addMinutes(5),
        'is_verified' => false,
    ]);

    $response = $this->post('/election/nominate-self', [
        'no_kartu' => '0008',
        'otp' => '123456',
    ]);

    $response->assertSessionHasNoErrors();
    $this->assertDatabaseHas('calons', [
        'member_id' => $member->id,
        'no_kartu' => '0008',
    ]);
});

test('nominateMember fails if nominator is ineligible', function () {
    $currentYear = intval(date('Y'));
    
    // Ineligible Nominator (under penalty)
    $nominator = createTestMember([
        'no_kartu' => '0009',
        'status_keanggotaan' => 'LIFE MEMBER',
        'terdaftar_sejak' => (string)($currentYear - 5),
        'penalty' => 'warning',
        'nik' => '1234567890123418',
    ]);

    // Eligible Candidate
    $candidate = createTestMember([
        'nama_lengkap' => 'Target Candidate',
        'no_kartu' => '0010',
        'status_keanggotaan' => 'LIFE MEMBER',
        'terdaftar_sejak' => (string)($currentYear - 15),
        'nik' => '1234567890123419',
    ]);

    Otp::create([
        'member_id' => $nominator->id,
        'otp' => '123456',
        'phone' => $nominator->no_wa,
        'expires_at' => now()->addMinutes(5),
        'is_verified' => false,
    ]);

    $response = $this->post('/election/nominate-member', [
        'candidate_name' => 'Target Candidate',
        'nominator_no_kartu' => '0009',
        'otp' => '123456',
    ]);

    $response->assertSessionHasErrors(['nominator_no_kartu']);
});

test('nominateMember fails if candidate is ineligible', function () {
    $currentYear = intval(date('Y'));
    
    // Eligible Nominator
    $nominator = createTestMember([
        'no_kartu' => '0011',
        'status_keanggotaan' => 'LIFE MEMBER',
        'terdaftar_sejak' => (string)($currentYear - 15),
        'nik' => '1234567890123420',
    ]);

    // Ineligible Candidate (registered 2 years ago)
    $candidate = createTestMember([
        'nama_lengkap' => 'Young Candidate',
        'no_kartu' => '0012',
        'status_keanggotaan' => 'LIFE MEMBER',
        'terdaftar_sejak' => (string)($currentYear - 2),
        'nik' => '1234567890123421',
    ]);

    Otp::create([
        'member_id' => $nominator->id,
        'otp' => '123456',
        'phone' => $nominator->no_wa,
        'expires_at' => now()->addMinutes(5),
        'is_verified' => false,
    ]);

    $response = $this->post('/election/nominate-member', [
        'candidate_name' => 'Young Candidate',
        'nominator_no_kartu' => '0011',
        'otp' => '123456',
    ]);

    $response->assertSessionHasErrors(['candidate_name']);
});

test('nominateMember succeeds if both are eligible', function () {
    $currentYear = intval(date('Y'));
    
    // Eligible Nominator
    $nominator = createTestMember([
        'nama_lengkap' => 'Nominator Member',
        'no_kartu' => '0013',
        'status_keanggotaan' => 'LIFE MEMBER',
        'terdaftar_sejak' => (string)($currentYear - 10),
        'nik' => '1234567890123422',
    ]);

    // Eligible Candidate
    $candidate = createTestMember([
        'nama_lengkap' => 'Good Candidate',
        'no_kartu' => '0014',
        'status_keanggotaan' => 'LIFE MEMBER',
        'terdaftar_sejak' => (string)($currentYear - 11),
        'nik' => '1234567890123423',
    ]);

    Otp::create([
        'member_id' => $nominator->id,
        'otp' => '123456',
        'phone' => $nominator->no_wa,
        'expires_at' => now()->addMinutes(5),
        'is_verified' => false,
    ]);

    $response = $this->post('/election/nominate-member', [
        'candidate_name' => 'Good Candidate',
        'nominator_no_kartu' => '0013',
        'otp' => '123456',
    ]);

    $response->assertSessionHasNoErrors();
    $this->assertDatabaseHas('calons', [
        'member_id' => $candidate->id,
        'diajukan_oleh' => $nominator->nama_lengkap,
    ]);
});

test('member cannot nominate self if they already nominated self or someone else', function () {
    $currentYear = intval(date('Y'));
    $member = createTestMember([
        'no_kartu' => '0015',
        'status_keanggotaan' => 'LIFE MEMBER',
        'terdaftar_sejak' => (string)($currentYear - 12),
        'nik' => '1234567890123424',
    ]);

    \App\Models\Calon::create([
        'member_id' => $member->id,
        'no_kartu' => '0015',
        'chapter' => 'Jakarta',
        'status' => 'mengajukan',
        'diajukan_oleh' => 'self',
        'no_kartu_diajukan_oleh' => '0015',
    ]);

    Otp::create([
        'member_id' => $member->id,
        'otp' => '123456',
        'phone' => $member->no_wa,
        'expires_at' => now()->addMinutes(5),
        'is_verified' => false,
    ]);

    $response = $this->post('/election/nominate-self', [
        'no_kartu' => '0015',
        'otp' => '123456',
    ]);

    $response->assertSessionHasErrors(['no_kartu']);
});

test('member cannot nominate someone else if they already nominated self or someone else', function () {
    $currentYear = intval(date('Y'));
    $nominator = createTestMember([
        'nama_lengkap' => 'Existing Nominator',
        'no_kartu' => '0016',
        'status_keanggotaan' => 'LIFE MEMBER',
        'terdaftar_sejak' => (string)($currentYear - 12),
        'nik' => '1234567890123425',
    ]);

    $candidate = createTestMember([
        'nama_lengkap' => 'New Candidate',
        'no_kartu' => '0017',
        'status_keanggotaan' => 'LIFE MEMBER',
        'terdaftar_sejak' => (string)($currentYear - 12),
        'nik' => '1234567890123426',
    ]);

    \App\Models\Calon::create([
        'member_id' => $nominator->id,
        'no_kartu' => '0016',
        'chapter' => 'Jakarta',
        'status' => 'mengajukan',
        'diajukan_oleh' => 'self',
        'no_kartu_diajukan_oleh' => '0016',
    ]);

    Otp::create([
        'member_id' => $nominator->id,
        'otp' => '123456',
        'phone' => $nominator->no_wa,
        'expires_at' => now()->addMinutes(5),
        'is_verified' => false,
    ]);

    $response = $this->post('/election/nominate-member', [
        'candidate_name' => 'New Candidate',
        'nominator_no_kartu' => '0016',
        'otp' => '123456',
    ]);

    $response->assertSessionHasErrors(['nominator_no_kartu']);
});

test('send-otp and member-info return clear error message for unregistered card before sending OTP', function () {
    $responseInfo = $this->getJson('/election/member-info/9999');
    $responseInfo->assertStatus(200);
    $responseInfo->assertJsonPath('success', false);
    $responseInfo->assertJsonPath('message', 'Nomor kartu 9999 tidak valid atau tidak terdaftar.');

    $responseOtp = $this->postJson('/api/send-otp', [
        'type' => 'self',
        'no_kartu' => '9999',
    ]);
    $responseOtp->assertStatus(404);
    $responseOtp->assertJsonPath('success', false);
    $responseOtp->assertJsonPath('message', 'Nomor kartu 9999 tidak valid atau tidak terdaftar.');
});

test('member who has been nominated by someone else can still nominate self without revealing nominator', function () {
    $currentYear = intval(date('Y'));
    $candidate = createTestMember([
        'nama_lengkap' => 'Candidate Member 1111',
        'no_kartu' => '1111',
        'status_keanggotaan' => 'LIFE MEMBER',
        'terdaftar_sejak' => (string)($currentYear - 12),
        'nik' => '1234567890111111',
    ]);

    $nominator = createTestMember([
        'nama_lengkap' => 'Nominator Member 2222',
        'no_kartu' => '2222',
        'status_keanggotaan' => 'LIFE MEMBER',
        'terdaftar_sejak' => (string)($currentYear - 12),
        'nik' => '1234567890222222',
    ]);

    // 2222 recommends 1111
    \App\Models\Calon::create([
        'member_id' => $candidate->id,
        'no_kartu' => '1111',
        'chapter' => 'Mother Chapter',
        'status' => 'diajukan',
        'diajukan_oleh' => 'Nominator Member 2222',
        'no_kartu_diajukan_oleh' => '2222',
    ]);

    // Check member-info for 1111 as candidate should succeed without revealing 2222
    $infoRes = $this->getJson('/election/member-info/1111?role=candidate');
    $infoRes->assertStatus(200);
    $infoRes->assertJsonPath('success', true);

    // Now 1111 attempts self-nomination
    Otp::create([
        'member_id' => $candidate->id,
        'otp' => '123456',
        'phone' => $candidate->no_wa,
        'expires_at' => now()->addMinutes(5),
        'is_verified' => false,
    ]);

    $response = $this->post('/election/nominate-self', [
        'no_kartu' => '1111',
        'otp' => '123456',
    ]);

    $response->assertSessionHasNoErrors();
    $this->assertDatabaseHas('calons', [
        'no_kartu' => '1111',
        'diajukan_oleh' => 'self',
    ]);
});

test('nominator who has nominated someone cannot nominate self or someone else', function () {
    $currentYear = intval(date('Y'));
    $nominator = createTestMember([
        'nama_lengkap' => 'Nominator Member 2222',
        'no_kartu' => '2222',
        'status_keanggotaan' => 'LIFE MEMBER',
        'terdaftar_sejak' => (string)($currentYear - 12),
        'nik' => '1234567890222223',
    ]);

    // 2222 already recommended 1111
    \App\Models\Calon::create([
        'member_id' => 1,
        'no_kartu' => '1111',
        'chapter' => 'Mother Chapter',
        'status' => 'diajukan',
        'diajukan_oleh' => 'Nominator Member 2222',
        'no_kartu_diajukan_oleh' => '2222',
    ]);

    Otp::create([
        'member_id' => $nominator->id,
        'otp' => '123456',
        'phone' => $nominator->no_wa,
        'expires_at' => now()->addMinutes(5),
        'is_verified' => false,
    ]);

    $resSelf = $this->post('/election/nominate-self', [
        'no_kartu' => '2222',
        'otp' => '123456',
    ]);
    $resSelf->assertSessionHasErrors(['no_kartu']);
});



