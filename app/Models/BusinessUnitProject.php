<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessUnitProject extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_unit_id',
        'project_id',
        'project_no',
        'name',
    ];

    public function businessUnit()
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    /**
     * Helper to get Project No by Business Unit ID and Project Name.
     * If not found, it returns the $fallback parameter (which is by default the $projectName).
     *
     * @param int|null $businessUnitId
     * @param string $projectName
     * @param string|null $fallback
     * @return string
     */
    public static function getProjectNoByBusinessUnit($businessUnitId, $projectName, $fallback = null)
    {
        if (is_null($fallback)) {
            $fallback = $projectName;
        }

        if (!$businessUnitId || !$projectName) {
            return $fallback;
        }

        $project = self::where('business_unit_id', $businessUnitId)
            ->where('name', trim(strtoupper($projectName)))
            ->first();

        return $project ? $project->project_no : $fallback;
    }
}
