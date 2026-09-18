<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SellPhoneResetLog extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'previous_accurate_docs_snapshot' => 'array',
        'previous_payments_snapshot' => 'array',
        'previous_appraised_value' => 'decimal:2',
        'new_appraised_value' => 'decimal:2',
    ];

    public function sellPhone(): BelongsTo
    {
        return $this->belongsTo(SellPhone::class);
    }

    public function resetBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reset_by');
    }

    public function previousProductAccurate(): BelongsTo
    {
        return $this->belongsTo(ProductAccurate::class, 'previous_product_accurate_id');
    }

    public function newProductAccurate(): BelongsTo
    {
        return $this->belongsTo(ProductAccurate::class, 'new_product_accurate_id');
    }
}
