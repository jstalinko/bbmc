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
        'foto',
        'no_kartu',
        'status_keanggotaan',
        'chapter',
        'checkpoint',
        'region',
        'terdaftar_sejak',
        'penalty',
        'penalty_reason',
    ];
}
