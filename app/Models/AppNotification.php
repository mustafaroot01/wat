<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppNotification extends Model
{
    protected $fillable = [
        'title',
        'message',
        'image',
        'delivery_status',
        'failure_reason',
        'sent_at'
    ];
    
    protected $casts = [
        'sent_at' => 'datetime',
    ];
}
