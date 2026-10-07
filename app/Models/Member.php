<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    protected $fillable = [
        'nama_lengkap',
        'nama_panggilan',
        'tempat_lahir',
        'tanggal_lahir',
        'jenis_kelamin',
        'gol_darah',
        'nik',
        'alamat',
        'no_wa',
        'email',
        'profesi',
        'jabatan',
        'foto',
        'no_kartu',
        'status_keanggotaan',
        'chapter',
        'checkpoint',
        'region',
        'terdaftar_sejak',
        'penalty',
        'penalty_reason',
        'offline_voter',
    ];

    protected $casts = [
        'offline_voter' => 'boolean',
    ];

    public function pollings()
    {
        return $this->hasMany(Polling::class, 'member_id');
    }
}
