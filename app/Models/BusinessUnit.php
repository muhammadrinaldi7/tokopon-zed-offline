<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessUnit extends Model
{
    protected $fillable = [
        'code',
        'name',
        'accurate_host',
        'accurate_token',
        'accurate_secret_key',
        'is_active',
    ];
}
