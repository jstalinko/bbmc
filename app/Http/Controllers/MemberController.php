<?php

namespace App\Http\Controllers;

use App\Models\Member;
use Illuminate\Http\Request;
use Inertia\Inertia;

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
            'tanggal_lahir'     => 'required|date',
            'jenis_kelamin'     => 'required|in:L,P',
            'gol_darah'         => 'required|in:A,B,AB,O',
            'nik'               => 'required|string|digits:16',
            'alamat'            => 'required|string',
            'no_wa'             => 'required|string|max:20',
            'email'             => 'nullable|email|max:255',
            'profesi'           => 'nullable|string|max:255',
            'foto'              => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'no_kartu'          => 'nullable|string|max:50',
            'status_keanggotaan'=> 'required|in:SS DIPONEGORO,LIFE MEMBER,HONORARY,VIRGIN,PROSPECT',
            'chapter'           => 'required|string|max:100',
            'checkpoint'        => 'nullable|string|max:100',
            'terdaftar_sejak'   => 'nullable|date',
            'jenis_motor'       => 'nullable|string|max:255',
            'tahun_motor'       => 'nullable|digits:4',
            'no_pol'            => 'nullable|string|max:20',
        ], [
            'nama_lengkap.required'       => 'Nama lengkap wajib diisi.',
            'nama_panggilan.required'     => 'Nama panggilan wajib diisi.',
            'tempat_lahir.required'       => 'Tempat lahir wajib diisi.',
            'tanggal_lahir.required'      => 'Tanggal lahir wajib diisi.',
            'jenis_kelamin.required'      => 'Jenis kelamin wajib dipilih.',
            'gol_darah.required'          => 'Golongan darah wajib dipilih.',
            'nik.required'                => 'NIK wajib diisi.',
            'nik.digits'                  => 'NIK harus tepat 16 digit angka.',
            'alamat.required'             => 'Alamat wajib diisi.',
            'no_wa.required'              => 'No. WhatsApp wajib diisi.',
            'email.email'                 => 'Format email tidak valid.',
            'foto.image'                  => 'File foto harus berupa gambar.',
            'foto.max'                    => 'Ukuran foto maksimal 5MB.',
            'status_keanggotaan.required' => 'Status keanggotaan wajib dipilih.',
            'chapter.required'            => 'Chapter wajib dipilih.',
            'tahun_motor.digits'          => 'Tahun motor harus 4 digit.',
        ]);

        if ($request->hasFile('foto')) {
            $validated['foto'] = $request->file('foto')->store('members/foto', 'public');
        }

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


    public function list(Request $request)
    {
        $query = Member::query()->orderBy('created_at', 'desc');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                  ->orWhere('no_kartu', 'like', "%{$search}%")
                  ->orWhere('no_wa', 'like', "%{$search}%")
                  ->orWhere('chapter', 'like', "%{$search}%")
                  ->orWhere('status_keanggotaan', 'like', "%{$search}%")
                  ->orWhere('checkpoint', 'like', "%{$search}%");
            });
        }

        $members = $query->paginate(10)->withQueryString();

        return Inertia::render('Member/list', [
            'members' => $members,
            'filters' => ['search' => $request->input('search', '')],
        ]);
    }

    public function show(Member $member)
    {
        return Inertia::render('Member/show', ['member' => $member]);
    }

    public function edit(Member $member)
    {
        return Inertia::render('Member/edit', ['member' => $member]);
    }

    public function destroy(Member $member)
    {
        if ($member->foto) {
            \Storage::disk('public')->delete($member->foto);
        }
        $member->delete();
        return redirect()->route('member.list')->with('success', 'Data anggota berhasil dihapus.');
    }
}
