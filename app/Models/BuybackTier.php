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
        $rawRules = $this->rules ?? [];
        $formattedRules = [];

        foreach ($rawRules as $category => $data) {
            // Backward compatibility: If $data is a sequential array, it's the old format
            if (is_array($data) && (empty($data) || array_keys($data) === range(0, count($data) - 1))) {
                // Automatically mark 'kelengkapan' as multiple for old data
                $isMultiple = str_contains(strtolower($category), 'kelengkapan');
                $formattedRules[$category] = [
                    'is_multiple' => $isMultiple,
                    'items'       => $data
                ];
            } else {
                // New format: associative array with 'is_multiple' and 'items'
                $formattedRules[$category] = $data;
            }
        }
        
        return $formattedRules;
    }


}
