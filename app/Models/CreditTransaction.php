<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditTransaction extends Model
{
    protected $fillable = ['type', 'quantity', 'price_iqd', 'note'];

    protected $casts = [
        'quantity'  => 'integer',
        'price_iqd' => 'integer',
    ];
}
