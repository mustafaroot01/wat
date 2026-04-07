<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FirebaseSetting extends Model
{
    protected $fillable = [
        'api_key',
        'auth_domain',
        'project_id',
        'storage_bucket',
        'messaging_sender_id',
        'app_id',
        'measurement_id',
        'default_topic'
    ];
}
