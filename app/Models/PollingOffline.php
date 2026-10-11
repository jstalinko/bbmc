<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PollingOffline extends Model
{
    protected $table = 'polling_offlines';

    protected $fillable = [
        'calon_id',
        'jumlah_suara',
        'penanggung_jawab',
        'foto_bukti',
        'keterangan',
        'created_by',
    ];

    protected $casts = [
        'jumlah_suara' => 'integer',
        'calon_id' => 'integer',
    ];

    protected $appends = [
        'foto_bukti_url',
    ];

    public function calon(): BelongsTo
    {
        return $this->belongsTo(Calon::class, 'calon_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getFotoBuktiUrlAttribute(): ?string
    {
        if (empty($this->foto_bukti)) {
            return null;
        }

        if (str_starts_with($this->foto_bukti, 'http://') || str_starts_with($this->foto_bukti, 'https://')) {
            return $this->foto_bukti;
        }

        return Storage::disk('public')->url($this->foto_bukti);
    }
}
