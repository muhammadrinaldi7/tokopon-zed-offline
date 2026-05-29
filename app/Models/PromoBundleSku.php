<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromoBundleSku extends Model
{
    protected $guarded = ['id'];

    public function promo()
    {
        return $this->belongsTo(Promo::class);
    }
}
