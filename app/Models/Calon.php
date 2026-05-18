<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Calon extends Model
{
    protected $fillable = [
        'member_id',
        'no_kartu',
        'chapter',
        'visi',
        'misi',
        'status',
        'diajukan_oleh',
        'no_kartu_diajukan_oleh',
        'foto_calon',
    ];
}
