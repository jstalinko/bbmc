<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ElectionQueue extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id',
        'status',
        'last_activity',
    ];

    protected $casts = [
        'last_activity' => 'datetime',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}
