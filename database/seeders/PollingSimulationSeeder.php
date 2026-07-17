<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Member;
use App\Models\Calon;
use App\Models\Polling;
use Illuminate\Support\Facades\DB;

class PollingSimulationSeeder extends Seeder
{
    /**
     * Run the database seeds to simulate Live Count Polling.
     */
    public function run(): void
    {
        $this->command->info('Mulai melakukan simulasi data Polling & Kandidat El Presidente BBMC...');

        // 1. Bersihkan data simulasi & cache sebelumnya agar idempotent
        \Illuminate\Support\Facades\Cache::forget('polling_throttled_state');
        DB::table('pollings')->truncate();
        
        // Bersihkan calon & member simulasi yang dibuat seeder ini (ditandai dengan no_kartu khusus SIM-*)
        $simulatedMemberIds = Member::where('no_kartu', 'like', 'SIM-%')->pluck('id');
        Calon::whereIn('member_id', $simulatedMemberIds)->delete();
        Member::whereIn('id', $simulatedMemberIds)->delete();

        // 2. Buat 4 Kandidat El Presidente (LIFE MEMBER & SS DIPONEGORO, terdaftar > 10 tahun, clean penalty)
        $candidatesData = [
            [
                'no_kartu' => 'SIM-0001',
                'nama_lengkap' => 'Budi Setiawan (Kang Budi)',
                'nama_panggilan' => 'Kang Budi',
                'chapter' => 'Bandung',
                'status_keanggotaan' => 'LIFE MEMBER',
                'terdaftar_sejak' => '2010',
                'visi' => 'Menjadikan Bikers Brotherhood MC Indonesia sebagai barometer persaudaraan sejati di nusantara & dunia.',
                'misi' => '1. Memperkuat pondasi Persaudaraan sejati.\n2. Digitalisasi sistem administrasi & keanggotaan.\n3. Program kemandirian ekonomi chapter.',
                'target_votes' => 96 // ~38.4%
            ],
            [
                'no_kartu' => 'SIM-0002',
                'nama_lengkap' => 'Dodi Hendra Kusuma (Kang Dodi)',
                'nama_panggilan' => 'Kang Dodi',
                'chapter' => 'Jakarta',
                'status_keanggotaan' => 'LIFE MEMBER',
                'terdaftar_sejak' => '2012',
                'visi' => 'Brotherhood For All - Mengembangkan nilai loyalitas, kehormatan dan kebanggaan dalam setiap sendi kehidupan member.',
                'misi' => '1. Pembinaan karakter & tata krama jalanan.\n2. Kolaborasi lintas klub dan masyarakat umum.\n3. Kegiatan sosial budaya berskala nasional.',
                'target_votes' => 70 // ~28.0%
            ],
            [
                'no_kartu' => 'SIM-0003',
                'nama_lengkap' => 'Hendra Pradana (Kang Hendra)',
                'nama_panggilan' => 'Kang Hendra',
                'chapter' => 'West Java',
                'status_keanggotaan' => 'SS DIPONEGORO',
                'terdaftar_sejak' => '2008',
                'visi' => 'Menjaga tradisi leluhur BBMC dengan mengutamakan rasa hormat dan kehormatan korps.',
                'misi' => '1. Pelestarian nilai-nilai sejarah BBMC.\n2. Penegakan disiplin dan etika berkendara.\n3. Peningkatan kualitas keanggotaan.',
                'target_votes' => 52 // ~20.8%
            ],
            [
                'no_kartu' => 'SIM-0004',
                'nama_lengkap' => 'Aria Wiratama (Kang Aria)',
                'nama_panggilan' => 'Kang Aria',
                'chapter' => 'Bali',
                'status_keanggotaan' => 'LIFE MEMBER',
                'terdaftar_sejak' => '2014',
                'visi' => 'Ekspansi global dan perkuatan eksistensi BBMC di panggung internasional dengan semangat persaudaraan tanpa batas.',
                'misi' => '1. Penguatan jaringan internasional & chapter luar negeri.\n2. Festival otomotif & kebudayaan tahunan.\n3. Transparansi keuangan & manajemen organisasi.',
                'target_votes' => 32 // ~12.8%
            ],
        ];

        $calonModels = [];

        foreach ($candidatesData as $data) {
            $member = Member::create([
                'nama_lengkap' => $data['nama_lengkap'],
                'nama_panggilan' => $data['nama_panggilan'],
                'tempat_lahir' => 'Bandung',
                'tanggal_lahir' => '12/05/1978',
                'jenis_kelamin' => 'Laki-laki',
                'gol_darah' => 'O',
                'nik' => '32730' . rand(100000000, 999999999),
                'alamat' => 'Jl. Persaudaraan No. ' . rand(1, 100) . ', ' . $data['chapter'],
                'no_wa' => '0812' . rand(10000000, 99999999),
                'email' => strtolower(str_replace(' ', '', $data['nama_panggilan'])) . '@bbmc.id',
                'profesi' => 'Entrepreneur',
                'no_kartu' => $data['no_kartu'],
                'status_keanggotaan' => $data['status_keanggotaan'],
                'chapter' => $data['chapter'],
                'terdaftar_sejak' => $data['terdaftar_sejak'],
                'penalty' => 'clean'
            ]);

            $calon = Calon::create([
                'member_id' => $member->id,
                'no_kartu' => $member->no_kartu,
                'chapter' => $member->chapter,
                'visi' => $data['visi'],
                'misi' => $data['misi'],
                'status' => 'ditetapkan', // Ditetapkan agar masuk ke perhitungan Polling
                'diajukan_oleh' => 'self',
                'no_kartu_diajukan_oleh' => $member->no_kartu
            ]);

            $calonModels[] = [
                'calon' => $calon,
                'member' => $member,
                'target_votes' => $data['target_votes']
            ];
        }

        $this->command->info('4 Kandidat El Presidente berhasil dibuat dengan status "ditetapkan".');

        // 3. Buat Member Pemilih & Simulasi Suara (Total 250 Suara)
        $this->command->info('Membuat 250 data pemilih dan menyalurkan suara ke kotak suara digital...');
        
        $voterCounter = 100;
        $totalVotesCount = 0;

        foreach ($calonModels as $item) {
            $target = $item['target_votes'];
            $calonId = $item['calon']->id;

            for ($i = 0; $i < $target; $i++) {
                $voterCounter++;
                $noKartuVoter = 'SIM-' . str_pad($voterCounter, 4, '0', STR_PAD_LEFT);

                // Buat member pemilih
                $voter = Member::create([
                    'nama_lengkap' => 'Voter Simulasi ' . $voterCounter,
                    'nama_panggilan' => 'Bro ' . $voterCounter,
                    'tempat_lahir' => 'Bandung',
                    'tanggal_lahir' => '01/01/1985',
                    'jenis_kelamin' => 'Laki-laki',
                    'gol_darah' => 'A',
                    'nik' => '3273' . rand(100000000000, 999999999999),
                    'alamat' => 'Markas BBMC Chapter ' . $item['member']->chapter,
                    'no_wa' => '0813' . rand(10000000, 99999999),
                    'email' => 'voter' . $voterCounter . '@bbmc.id',
                    'profesi' => 'Bikers',
                    'no_kartu' => $noKartuVoter,
                    'status_keanggotaan' => 'LIFE MEMBER',
                    'chapter' => $item['member']->chapter,
                    'terdaftar_sejak' => '2015',
                    'penalty' => 'clean'
                ]);

                // Simpan rekam jejak vote ke tabel pollings
                Polling::create([
                    'member_id' => $voter->id,
                    'calon_id' => $calonId
                ]);

                $totalVotesCount++;
            }
        }

        $this->command->info('------------------------------------------------------------');
        $this->command->info('SIMULASI LIVE COUNT POLLING SELESAI!');
        $this->command->info("Total Suara Masuk Terverifikasi : {$totalVotesCount} Suara");
        $this->command->info('Distribusi Suara Sementara :');
        
        foreach ($calonModels as $item) {
            $pct = round(($item['target_votes'] / $totalVotesCount) * 100, 1);
            $this->command->info(" - {$item['member']->nama_lengkap} : {$item['target_votes']} Suara ({$pct}%)");
        }
        
        $this->command->info('------------------------------------------------------------');
        $this->command->info('Buka halaman http://localhost:8000/election/polling untuk melihat hasil Live Count & Delayed Suspense dalam aksi!');
    }
}
