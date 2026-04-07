<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class District extends Model
{
    protected $fillable = ['name', 'is_active'];

    public function areas()
    {
        return $this->hasMany(Area::class);
    }
}
