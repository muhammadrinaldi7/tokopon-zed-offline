<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    /**
     * Kolom yang dapat diisi secara massal.
     *
     * @var array
     */
    protected $fillable = [
        'accurate_vendor_id',
        'vendor_no',
        'vendor_name',
        'email',
        'phone',
    ];
}
