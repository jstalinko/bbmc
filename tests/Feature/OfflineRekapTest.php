<?php

use App\Models\Member;
use App\Models\User;
use App\Models\Calon;
use App\Models\Polling;
use App\Models\PollingOffline;
use App\Models\OfflineLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
    $this->settingPath = storage_path('app/private/pemilihan-setting.json');
    $dir = dirname($this->settingPath);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    file_put_contents($this->settingPath, json_encode([
        'ajukan_diri' => true,
        'ajukan_anggota' => true,
        'tampilkan_kandidat_polling' => true,
        'tanggal_mulai' => null,
        'tanggal_selesai' => null,
    ]));
});

afterEach(function () {
    if (file_exists($this->settingPath)) {
        unlink($this->settingPath);
    }
});

function createRekapMember(array $attributes = []) {
    return Member::create(array_merge([
        'nama_lengkap' => 'Kang Test',
        'nama_panggilan' => 'Kang',
        'tempat_lahir' => 'Bandung',
        'tanggal_lahir' => '01/01/1990',
        'jenis_kelamin' => 'L',
        'gol_darah' => 'O',
        'nik' => '1234567890123456',
        'alamat' => 'Jl. Test No. 1',
        'no_wa' => '628123456789',
        'no_kartu' => '0099',
        'status_keanggotaan' => 'LIFE MEMBER',
        'chapter' => 'Mother Chapter',
        'terdaftar_sejak' => '2010',
        'penalty' => 'clean',
        'offline_voter' => false,
    ], $attributes));
}

test('admin can view /pemilihan-offline with rekap data', function () {
    $admin = User::factory()->create();

    $response = $this->actingAs($admin)->get('/pemilihan-offline');
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => 
        $page->component('Election/Offline', false)
             ->has('rekap_offline')
             ->has('candidates')
             ->has('stats.total_rekap_offline')
    );
});

test('admin can store offline vote recap without candidate and with file proof', function () {
    Storage::fake('public');
    $admin = User::factory()->create();

    $file = UploadedFile::fake()->image('bukti_c1.jpg');

    $response = $this->actingAs($admin)->post('/pemilihan-offline/rekap', [
        'jumlah_suara' => 75,
        'penanggung_jawab' => 'Kang Asep (Ketua TPS 1)',
        'keterangan' => 'Perhitungan plano TPS 1 Bandung',
        'foto_bukti' => $file,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('polling_offlines', [
        'jumlah_suara' => 75,
        'penanggung_jawab' => 'Kang Asep (Ketua TPS 1)',
        'keterangan' => 'Perhitungan plano TPS 1 Bandung',
        'created_by' => $admin->id,
    ]);

    $rekap = PollingOffline::first();
    expect($rekap->foto_bukti)->not()->toBeNull();
    Storage::disk('public')->assertExists($rekap->foto_bukti);
});

test('admin can store offline vote recap linked to a specific candidate', function () {
    $admin = User::factory()->create();
    $member = createRekapMember(['nama_lengkap' => 'Calon El Presidente 1']);
    $calon = Calon::create([
        'member_id' => $member->id,
        'no_kartu' => $member->no_kartu,
        'chapter' => 'Mother Chapter',
        'status' => 'ditetapkan',
        'no_urut' => 1,
    ]);

    $response = $this->actingAs($admin)->post('/pemilihan-offline/rekap', [
        'calon_id' => $calon->id,
        'jumlah_suara' => 120,
        'penanggung_jawab' => 'Saksi TPS 02',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('polling_offlines', [
        'calon_id' => $calon->id,
        'jumlah_suara' => 120,
        'penanggung_jawab' => 'Saksi TPS 02',
    ]);
});

test('validation requires jumlah_suara and penanggung_jawab', function () {
    $admin = User::factory()->create();

    $response = $this->actingAs($admin)->post('/pemilihan-offline/rekap', [
        'jumlah_suara' => '',
        'penanggung_jawab' => '',
    ]);

    $response->assertSessionHasErrors(['jumlah_suara', 'penanggung_jawab']);
});

test('admin can delete offline vote recap and associated file is deleted', function () {
    Storage::fake('public');
    $admin = User::factory()->create();

    $file = UploadedFile::fake()->image('bukti_plano.png');
    $path = $file->store('polling_offline_proofs', 'public');

    $rekap = PollingOffline::create([
        'jumlah_suara' => 50,
        'penanggung_jawab' => 'Saksi TPS',
        'foto_bukti' => $path,
        'created_by' => $admin->id,
    ]);

    Storage::disk('public')->assertExists($path);

    $response = $this->actingAs($admin)->delete("/pemilihan-offline/rekap/{$rekap->id}");
    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->assertDatabaseMissing('polling_offlines', ['id' => $rekap->id]);
    Storage::disk('public')->assertMissing($path);
});

test('Polling model sums online and offline votes correctly', function () {
    $voterOnline1 = createRekapMember(['nama_lengkap' => 'Voter 1', 'no_kartu' => 'V001']);
    $voterOnline2 = createRekapMember(['nama_lengkap' => 'Voter 2', 'no_kartu' => 'V002']);

    $calonMember = createRekapMember(['nama_lengkap' => 'Calon Satu', 'no_kartu' => 'C001']);
    $calon = Calon::create([
        'member_id' => $calonMember->id,
        'no_kartu' => $calonMember->no_kartu,
        'chapter' => 'Mother Chapter',
        'status' => 'ditetapkan',
        'no_urut' => 1,
    ]);

    // 2 online votes
    Polling::create(['member_id' => $voterOnline1->id, 'calon_id' => $calon->id]);
    Polling::create(['member_id' => $voterOnline2->id, 'calon_id' => $calon->id]);

    // 45 offline votes
    PollingOffline::create([
        'calon_id' => $calon->id,
        'jumlah_suara' => 45,
        'penanggung_jawab' => 'Saksi TPS 1',
    ]);

    $pollingModel = new Polling();
    // Candidate total vote: 2 online + 45 offline = 47
    expect($pollingModel->totalVoteByMember($calonMember->id))->toBe(47);
    expect($pollingModel->totalVote($calon->id))->toBe(47);
    expect(Polling::totalOnlineVotes())->toBe(2);
    expect(Polling::totalOfflineVotes())->toBe(45);
    expect(Polling::totalVoting())->toBe(47);

    $results = $pollingModel->resultVotes();
    expect($results[0]['total_vote'])->toBe(47);
});

test('polling page reflects offline rekap votes in total votes and distribution', function () {
    $voterOnline = createRekapMember(['nama_lengkap' => 'Online Voter', 'no_kartu' => 'O001']);
    $calonMember = createRekapMember(['nama_lengkap' => 'Calon Presidente', 'no_kartu' => 'C001']);
    $calon = Calon::create([
        'member_id' => $calonMember->id,
        'no_kartu' => $calonMember->no_kartu,
        'chapter' => 'Mother Chapter',
        'status' => 'ditetapkan',
        'no_urut' => 1,
    ]);

    Polling::create(['member_id' => $voterOnline->id, 'calon_id' => $calon->id]);

    PollingOffline::create([
        'calon_id' => $calon->id,
        'jumlah_suara' => 30,
        'penanggung_jawab' => 'Saksi TPS',
    ]);

    $response = $this->get('/election/polling');
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => 
        $page->component('Election/polling', false)
             ->where('totalSuaraOffline', 30)
             ->where('totalSuaraOnline', 1)
             ->where('totalSuaraMasuk', 31)
    );
});
