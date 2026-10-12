<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Calon extends Model
{
    protected $fillable = [
        'member_id',
        'no_urut',
        'no_kartu',
        'chapter',
        'visi',
        'misi',
        'status',
        'diajukan_oleh',
        'no_kartu_diajukan_oleh',
        'foto_calon',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    public function getFotoCalonAttribute($value)
    {
        if ($this->relationLoaded('member') && !empty($this->member?->foto)) {
            return $this->member->foto;
        }

        if (!empty($this->member?->foto)) {
            return $this->member->foto;
        }

        return $value;
    }
}
