<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccurateGlAccount extends Model
{
    protected $fillable = [
        'account_no',
        'name',
        'account_type',
        'database_source'
    ];
}
