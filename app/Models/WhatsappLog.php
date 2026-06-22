<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappLog extends Model
{
    protected $fillable = [
        'batch_id',
        'recipient_name',
        'recipient_phone',
        'message',
        'status',
        'response',
    ];
}
