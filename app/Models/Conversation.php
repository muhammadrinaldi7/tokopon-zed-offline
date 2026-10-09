<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable = [
        'user_id',
        'status',
        'business_unit_id',
        'guest_token',
        'guest_name',
        'guest_phone',
        'product_accurate_id',
        'closing_status',
        'closing_amount',
        'closing_notes',
        'closed_by_user_id',
        'closed_at',
    ];

    protected $casts = [
        'closing_amount' => 'decimal:2',
        'closed_at' => 'datetime',
    ];

    public function closedBy()
    {
        return $this->belongsTo(User::class, 'closed_by_user_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function businessUnit()
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    public function productAccurate()
    {
        return $this->belongsTo(ProductAccurate::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function latestMessage()
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function unreadCountForCs()
    {
        return $this->messages()
            ->whereNull('read_at')
            ->whereIn('sender_type', ['customer', 'guest'])
            ->count();
    }
}
