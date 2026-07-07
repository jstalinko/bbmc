<?php

namespace App\Http\Controllers;

use App\Models\Member;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;

class MemberController extends Controller
{
    public function index()
    {
        return redirect('/member/register');
    }

    public function register()
    {
        return Inertia::render('Member/register');
    }

    public function registerPost(Request $request)
    {
        $validated = $request->validate([
            'nama_lengkap'      => 'required|string|max:255',
            'nama_panggilan'    => 'required|string|max:255',
            'tempat_lahir'      => 'required|string|max:255',
            'tanggal_lahir'     => ['required', 'string', 'regex:/^\d{2}\/\d{2}\/\d{4}$/'],
            'jenis_kelamin'     => 'required|in:L,P',
            'gol_darah'         => 'required|in:A,B,AB,O',
            'nik'               => 'nullable|string|digits:16|unique:members,nik',
            'alamat'            => 'required|string',
            'no_wa'             => 'required|string|max:20|unique:members,no_wa',
            'email'             => 'nullable|email|max:255',
            'profesi'           => 'nullable|string|max:255',
            'foto'              => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'no_kartu'          => 'required|digits:4|numeric|max:1500|unique:members,no_kartu',
            'status_keanggotaan'=> 'required|in:SS DIPONEGORO,LIFE MEMBER,HONORARY,VIRGIN,PROSPECT',
            'chapter'           => 'required|string|max:100',
            'checkpoint'        => 'nullable|string|max:100',
            'region'            => 'nullable|string|max:100',
            'terdaftar_sejak'   => 'nullable|string|digits:4',
            'agreed'            => 'accepted',
        ], [
            'nama_lengkap.required'       => 'Nama lengkap wajib diisi.',
            'nama_panggilan.required'     => 'Nama panggilan wajib diisi.',
            'tempat_lahir.required'       => 'Tempat lahir wajib diisi.',
            'tanggal_lahir.required'      => 'Tanggal lahir wajib diisi.',
            'tanggal_lahir.regex'         => 'Format tanggal lahir harus DD/MM/YYYY.',
            'jenis_kelamin.required'      => 'Jenis kelamin wajib dipilih.',
            'gol_darah.required'          => 'Golongan darah wajib dipilih.',
            'nik.digits'                  => 'NIK harus tepat 16 digit angka.',
            'nik.unique'                  => 'NIK sudah terdaftar.',
            'alamat.required'             => 'Alamat wajib diisi.',
            'no_wa.required'              => 'No. WhatsApp wajib diisi.',
            'no_wa.unique'                => 'No. WhatsApp sudah terdaftar.',
            'email.email'                 => 'Format email tidak valid.',
            'foto.image'                  => 'File foto harus berupa gambar.',
            'foto.max'                    => 'Ukuran foto maksimal 5MB.',
            'no_kartu.required'           => 'No. Kartu wajib diisi.',
            'no_kartu.digits'             => 'No. Kartu harus tepat 4 digit angka.',
            'no_kartu.max'                => 'No. Kartu maksimal 1500.',
            'no_kartu.unique'             => 'No. Kartu sudah terdaftar.',
            'status_keanggotaan.required' => 'Status keanggotaan wajib dipilih.',
            'chapter.required'            => 'Chapter wajib dipilih.',
            'terdaftar_sejak.digits'      => 'Tahun terdaftar harus 4 digit angka.',
            'agreed.accepted'             => 'Anda harus menyetujui Syarat Ketentuan dan Kebijakan Privasi.',
        ]);

        if ($request->hasFile('foto')) {
            $validated['foto'] = $request->file('foto')->store('members/foto', 'public');
        }

        unset($validated['agreed']);

        Member::create($validated);

        return redirect('/member/register-success')->with('success', 'Pendaftaran berhasil dikirim! Kami akan menghubungi Anda segera.');
    }
    public function registerSuccess()
    {
        if(!session()->has('success')) {
            return redirect('/member/register');
        }
        
        return Inertia::render('Member/register-success');
    }

    public function termsIntegrity()
    {
        return Inertia::render('Member/TermsIntegrity');
    }


    private function buildExportQuery(Request $request)
    {
        $query = Member::query()->orderBy('created_at', 'desc');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                  ->orWhere('nama_panggilan', 'like', "%{$search}%")
                  ->orWhere('no_kartu', 'like', "%{$search}%")
                  ->orWhere('no_wa', 'like', "%{$search}%")
                  ->orWhere('chapter', 'like', "%{$search}%")
                  ->orWhere('status_keanggotaan', 'like', "%{$search}%")
                  ->orWhere('checkpoint', 'like', "%{$search}%");
            });
        }

        $filterLm = $request->input('filter_lm', '');
        if ($filterLm === '10') {
            $cutoffYear = intval(date('Y')) - 10;
            $query->where('status_keanggotaan', 'LIFE MEMBER')
                  ->whereNotNull('terdaftar_sejak')
                  ->where('terdaftar_sejak', '<=', $cutoffYear);
        } elseif ($filterLm === '10_under') {
            $cutoffYear = intval(date('Y')) - 10;
            $query->where('status_keanggotaan', 'LIFE MEMBER')
                  ->whereNotNull('terdaftar_sejak')
                  ->where('terdaftar_sejak', '>=', $cutoffYear);
        }

        return $query;
    }

    public function list(Request $request)
    {
        $query = $this->buildExportQuery($request);
        $members = $query->paginate(10)->withQueryString();

        return Inertia::render('Member/list', [
            'members' => $members,
            'filters' => [
                'search' => $request->input('search', ''),
                'filter_lm' => $request->input('filter_lm', ''),
            ],
        ]);
    }

    public function exportCsv(Request $request)
    {
        $members = $this->buildExportQuery($request)->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="data_anggota_' . date('Ymd_His') . '.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($members) {
            $file = fopen('php://output', 'w');
            fputs($file, "\xEF\xBB\xBF"); // UTF-8 BOM for proper Excel compatibility

            fputcsv($file, [
                'No. Kartu',
                'Nama Lengkap',
                'Nama Panggilan',
                'NIK',
                'Tempat Lahir',
                'Tanggal Lahir',
                'Jenis Kelamin',
                'Gol. Darah',
                'Alamat',
                'No. WA',
                'Email',
                'Profesi',
                'Status Keanggotaan',
                'Chapter',
                'Checkpoint',
                'Region',
                'Terdaftar Sejak',
            ]);

            foreach ($members as $m) {
                fputcsv($file, [
                    $m->no_kartu ? "BBMC 38 2026 {$m->no_kartu}" : '—',
                    $m->nama_lengkap,
                    $m->nama_panggilan,
                    $m->nik ?? '—',
                    $m->tempat_lahir,
                    $m->tanggal_lahir,
                    $m->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan',
                    $m->gol_darah,
                    $m->alamat,
                    $m->no_wa,
                    $m->email ?? '—',
                    $m->profesi ?? '—',
                    $m->status_keanggotaan,
                    $m->chapter,
                    $m->checkpoint ?? '—',
                    $m->region ?? '—',
                    $m->terdaftar_sejak ?? '—',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportPdf(Request $request)
    {
        $members = $this->buildExportQuery($request)->get();

        $filterLm = $request->input('filter_lm', '');
        $filterDesc = '';
        if ($filterLm === '10') {
            $filterDesc = 'Life Member >= 10 Tahun';
        } elseif ($filterLm === '10_under') {
            $filterDesc = 'Life Member <= 10 Tahun';
        }

        if ($search = $request->input('search')) {
            $filterDesc .= ($filterDesc ? ' & ' : '') . 'Pencarian: "' . $search . '"';
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('member.export-pdf', [
            'members' => $members,
            'filter_desc' => $filterDesc,
        ]);

        return $pdf->download('daftar_anggota_' . date('Ymd_His') . '.pdf');
    }

    public function show(Member $member)
    {
        return Inertia::render('Member/show', ['member' => $member]);
    }

    public function edit(Member $member)
    {
        return Inertia::render('Member/edit', ['member' => $member]);
    }

    private function generateQrDataUri(Member $member): string
    {
        $url = url('/member/' . $member->no_kartu);
        $qr  = new QrCode(
            data: $url,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 200,
            margin: 4,
            foregroundColor: new Color(0, 0, 0),
            backgroundColor: new Color(255, 255, 255),
        );
        $writer = new PngWriter();
        $result = $writer->write($qr);
        return 'data:image/png;base64,' . base64_encode($result->getString());
    }

    public function printCard(Member $member)
    {
        $qrDataUri = $this->generateQrDataUri($member);
        return view('member.print-card', ['member' => $member, 'qrDataUri' => $qrDataUri]);
    }

    public function printCardPdf(Member $member)
    {
        $qrDataUri = $this->generateQrDataUri($member);
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('member.print-card-pdf', ['member' => $member, 'qrDataUri' => $qrDataUri]);
        // CR80: 85.6mm × 54mm in points. Pass width>height directly as landscape (no orientation flag needed).
        $pdf->setPaper([0, 0, 242.64, 153.07]);
        return $pdf->download("kartu-bbmc-registration-{$member->no_kartu}.pdf");
    }

    public function update(Request $request, Member $member)
    {
        $validated = $request->validate([
            'nama_lengkap'       => 'required|string|max:255',
            'nama_panggilan'     => 'required|string|max:255',
            'tempat_lahir'       => 'required|string|max:255',
            'tanggal_lahir'      => ['required', 'string', 'regex:/^\d{2}\/\d{2}\/\d{4}$/'],
            'jenis_kelamin'      => 'required|in:L,P',
            'gol_darah'          => 'required|in:A,B,AB,O,-',
            'nik'                => 'nullable|string|digits:16|unique:members,nik,' . $member->id,
            'alamat'             => 'required|string',
            'no_wa'              => 'required|string|max:20|unique:members,no_wa,' . $member->id,
            'email'              => 'nullable|email|max:255',
            'profesi'            => 'nullable|string|max:255',
            'foto'               => 'nullable|image|mimes:jpg,jpeg,png,webp,heic,heif,gif,bmp,HEIC,HEIF,GIF,BMP|max:5120',
            'no_kartu'           => 'nullable|digits:4|unique:members,no_kartu,' . $member->id,
            'status_keanggotaan' => 'required|in:SS DIPONEGORO,LIFE MEMBER,HONORARY,VIRGIN,PROSPECT',
            'chapter'            => 'required|string|max:100',
            'checkpoint'         => 'nullable|string|max:100',
            'region'             => 'nullable|string|max:100',
            'terdaftar_sejak'    => 'nullable|string|digits:4',
        ], [
            'nama_lengkap.required'       => 'Nama lengkap wajib diisi.',
            'nama_panggilan.required'     => 'Nama panggilan wajib diisi.',
            'tempat_lahir.required'       => 'Tempat lahir wajib diisi.',
            'tanggal_lahir.required'      => 'Tanggal lahir wajib diisi.',
            'tanggal_lahir.regex'         => 'Format tanggal lahir harus DD/MM/YYYY.',
            'jenis_kelamin.required'      => 'Jenis kelamin wajib dipilih.',
            'gol_darah.required'          => 'Golongan darah wajib dipilih.',
            'nik.digits'                  => 'NIK harus tepat 16 digit angka.',
            'nik.unique'                  => 'NIK sudah terdaftar.',
            'alamat.required'             => 'Alamat wajib diisi.',
            'no_wa.required'              => 'No. WhatsApp wajib diisi.',
            'no_wa.unique'                => 'No. WhatsApp sudah terdaftar.',
            'email.email'                 => 'Format email tidak valid.',
            'foto.image'                  => 'File foto harus berupa gambar.',
            'foto.max'                    => 'Ukuran foto maksimal 5MB.',
            'no_kartu.unique'             => 'No. Kartu sudah terdaftar.',
            'status_keanggotaan.required' => 'Status keanggotaan wajib dipilih.',
            'chapter.required'            => 'Chapter wajib dipilih.',
            'terdaftar_sejak.digits'      => 'Tahun terdaftar harus 4 digit angka.',
        ]);

        if ($request->hasFile('foto')) {
            // Hapus foto lama jika ada
            if ($member->foto) {
                \Storage::disk('public')->delete($member->foto);
            }
            $validated['foto'] = $request->file('foto')->store('members/foto', 'public');
        } else {
            // Jangan timpa foto lama jika tidak ada file baru
            unset($validated['foto']);
        }

        $member->update($validated);

        return back()->with('success', 'Data anggota berhasil diperbarui.');
    }

    public function destroy(Member $member)
    {
        if ($member->foto) {
            \Storage::disk('public')->delete($member->foto);
        }
        $member->delete();
        return redirect()->route('member.list')->with('success', 'Data anggota berhasil dihapus.');
    }


    public function showPublic($no_kartu)
    {
        $member = Member::where('no_kartu', $no_kartu)->first();
        if (!$member) {
            return redirect('/member/register')->with('error', 'Member tidak ditemukan.');
        }
        return Inertia::render('Member/show-public', ['member' => $member]);
    }

    public function validateCard(Request $request)
    {
        if (!$request->has('no_kartu')) {
            return Inertia::render('Member/validate');
        }

        $validated = $request->validate([
            'no_kartu' => 'required|digits:4',
        ], [
            'no_kartu.required' => 'Nomor kartu wajib diisi.',
            'no_kartu.digits' => 'Nomor kartu harus tepat 4 digit angka.',
        ]);

        $member = Member::where('no_kartu', $validated['no_kartu'])->first();

        if (!$member) {
            return back()->withErrors([
                'no_kartu' => 'Nomor kartu tidak terdaftar sebagai anggota.'
            ]);
        }

        return redirect()->route('member.show_public', ['no_kartu' => $member->no_kartu]);
    }
}
