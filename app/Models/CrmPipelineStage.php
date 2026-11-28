<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrmPipelineStage extends Model
{
    public const TYPE_OPEN = 'open';
    public const TYPE_WON = 'won';
    public const TYPE_LOST = 'lost';

    protected $fillable = [
        'name',
        'slug',
        'color',
        'stage_type',
        'sort_order',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function leads(): HasMany
    {
        return $this->hasMany(CrmLead::class, 'stage_id');
    }

    public function isWon(): bool
    {
        return $this->stage_type === self::TYPE_WON;
    }

    public function isLost(): bool
    {
        return $this->stage_type === self::TYPE_LOST;
    }
}
