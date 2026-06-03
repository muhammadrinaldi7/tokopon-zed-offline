<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employe extends Model
{
    use HasFactory;

    protected $fillable = [
        'accurate_employee_id',
        'employee_no',
        'name',
        'email',
        'phone_number',
        'position',
        'is_active',
        'user_id',
        'branch_id'
    ];

    /**
     * Hubungan ke User login POS (jika ada)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relasi one-to-many ke Order (karyawan yang menangani order)
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
