<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BuybackTier extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'rules'     => 'array',
    ];

    // Relasi ke perangkat-perangkat yang masuk di tier ini
    public function devices()
    {
        return $this->hasMany(BuybackDevice::class);
    }


    /**
     * Ambil semua kategori yang ada dalam rules JSON.
     * Return: array kategori => array item rules
     */
    public function getRulesByCategory(): array
    {
        return $this->rules ?? [];
    }


}
