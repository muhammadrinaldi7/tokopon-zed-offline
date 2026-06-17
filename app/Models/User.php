<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class User extends Authenticatable implements HasMedia
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, InteractsWithMedia;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'provider',
        'provider_id',
        'avatar',
        'identity',
        'npwp',
        'business_unit_id',
        'warehouse_id',
        'branch_id'
    ];

    public function businessUnit()
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    public function getActiveBusinessUnitId()
    {
        if ($this->hasAnyRole(['superadmin', 'admin', 'director'])) {
            return session('active_business_unit_id', $this->business_unit_id);
        }
        return $this->business_unit_id;
    }

    public function getActiveBusinessUnit()
    {
        if ($this->hasAnyRole(['superadmin', 'admin', 'director'])) {
            return BusinessUnit::find($this->getActiveBusinessUnitId());
        }
        return $this->business_unit;
    }

    public function accurateCustomers()
    {
        return $this->hasMany(UserAccurateCustomer::class);
    }

    public function getAccurateCustomerNo($businessUnitCode = 'syihab')
    {
        $pivot = $this->accurateCustomers()
            ->whereHas('businessUnit', function ($q) use ($businessUnitCode) {
                $q->where('code', $businessUnitCode);
            })
            ->first();

        return $pivot ? $pivot->accurate_customer_no : 'CASH';
    }

    public function getAccurateCustomerNoAttribute()
    {
        $bu = \App\Models\BusinessUnit::find($this->getActiveBusinessUnitId());
        $code = $bu ? $bu->code : 'syihab';
        $pivot = $this->accurateCustomers()->whereHas('businessUnit', function ($q) use ($code) {
            $q->where('code', $code);
        })->first();

        return $pivot ? $pivot->accurate_customer_no : null;
    }

    public function getAccurateCustomerIdAttribute()
    {
        $bu = \App\Models\BusinessUnit::find($this->getActiveBusinessUnitId());
        $code = $bu ? $bu->code : 'syihab';
        $pivot = $this->accurateCustomers()->whereHas('businessUnit', function ($q) use ($code) {
            $q->where('code', $code);
        })->first();

        return $pivot ? $pivot->accurate_customer_id : null;
    }

    public function accurateVendors()
    {
        return $this->hasMany(UserAccurateVendor::class);
    }

    public function getAccurateVendorNo($businessUnitCode = 'syihab')
    {
        $pivot = $this->accurateVendors()
            ->whereHas('businessUnit', function ($q) use ($businessUnitCode) {
                $q->where('code', $businessUnitCode);
            })
            ->first();

        return $pivot ? $pivot->accurate_vendor_no : null;
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function profile()
    {
        return $this->hasOne(\App\Models\UserProfile::class);
    }

    public function addresses()
    {
        return $this->hasMany(\App\Models\UserAddress::class);
    }

    public function bankAccounts()
    {
        return $this->hasMany(\App\Models\UserBankAccount::class);
    }

    public function orders()
    {
        return $this->hasMany(\App\Models\Order::class);
    }

    public function sellPhones()
    {
        return $this->hasMany(\App\Models\SellPhone::class);
    }

    public function conversations()
    {
        return $this->hasMany(\App\Models\Conversation::class);
    }

    public function cart()
    {
        return $this->hasOne(\App\Models\Cart::class);
    }

    public function getUserPermissions()
    {
        return $this->getAllPermissions()->mapWithKeys(fn($permission) => [$permission['name'] => true]);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
}
