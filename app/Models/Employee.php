<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $table = 'employees';
    protected $fillable = [
        'user_id',
        'division_id',
        'position_id',
        'employee_number',
        'region',
        'nik',
        'name',
        'gender',
        'place_of_birth',
        'date_of_birth',
        'phone_number',
        'address',
        'status',
        'email',
        'date_of_entry',
        'is_active',
        'photo'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function division()
    {
        return $this->belongsTo(Division::class, 'division_id');
    }

    public function position()
    {
        return $this->belongsTo(Position::class, 'position_id');
    }

    public function reports()
    {
        return $this->hasMany(Report::class, 'employee_id');
    }
    
}
