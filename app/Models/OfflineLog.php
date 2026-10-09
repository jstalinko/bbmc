<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfflineLog extends Model
{
    public const STATUS_ANTREAN = 'antrean';
    public const STATUS_SUDAH_MEMILIH = 'sudah_memilih';
    public const STATUS_TIDAK_MEMILIH = 'tidak_memilih';
    public const STATUS_CANCEL_VOTE = 'cancel_vote';

    public const STATUSES = [
        self::STATUS_ANTREAN,
        self::STATUS_SUDAH_MEMILIH,
        self::STATUS_TIDAK_MEMILIH,
        self::STATUS_CANCEL_VOTE,
    ];

    public const STATUS_LABELS = [
        self::STATUS_ANTREAN => 'Dalam Antrean',
        self::STATUS_SUDAH_MEMILIH => 'Sudah Memilih',
        self::STATUS_TIDAK_MEMILIH => 'Tidak Memilih',
        self::STATUS_CANCEL_VOTE => 'Batal Memilih',
    ];

    protected $fillable = [
        'nama_pengurus',
        'kode_akses',
        'member_id',
        'no_antrian',
        'no_tps',
        'voting_status',
    ];

    protected $casts = [
        'no_antrian' => 'integer',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->voting_status] ?? $this->voting_status;
    }
}
