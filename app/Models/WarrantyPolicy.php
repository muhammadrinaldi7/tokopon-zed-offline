<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarrantyPolicy extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'coverage_type',
        'duration_days',
        'brand_rule',
        'brand_list',
        'addon_trigger_keywords',
        'is_active',
        'business_unit_id',
        'coverage_scope',
    ];

    protected $casts = [
        'brand_list' => 'array',
        'addon_trigger_keywords' => 'array', // Now using proper array cast instead of manual json_decode
        'is_active' => 'boolean',
        'coverage_scope' => 'array',
    ];

    public function warranties()
    {
        return $this->hasMany(Warranty::class);
    }

    public function businessUnit()
    {
        return $this->belongsTo(BusinessUnit::class, 'business_unit_id');
    }

    public function getCoverageAttribute()
    {
        $scope = is_array($this->coverage_scope) ? $this->coverage_scope : [];
        $hasFactoryDefect = in_array('factory_defect', $scope);
        $hasHumanError = in_array('human_error', $scope);

        if ($this->type === 'addon_warranty') {
            return [
                ['name' => 'Cacat Pabrik (Mati Total, Layar, dll)', 'covered' => $hasFactoryDefect],
                ['name' => 'Kelalaian Pengguna (Jatuh, Pecah, Air)', 'covered' => $hasHumanError],
            ];
        }

        return [
            ['name' => 'Cacat Pabrik (Mesin, Layar, dll)', 'covered' => $hasFactoryDefect],
            ['name' => 'Kelalaian Pengguna (Jatuh, Air, dll)', 'covered' => $hasHumanError],
        ];
    }
}

