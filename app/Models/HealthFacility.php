<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class HealthFacility extends Model
{
    use HasFactory;

    protected $table = "health_facilities";

    protected $fillable = [
        'type_of_health_facility_id', 
        'name', 
        'slug', 
        'email', 
        'city', 
        'phone_number', 
        'address'
    ];

    public function type()
    {
        return $this->belongsTo(TypeOfHealthFacility::class, 'type_of_health_facility_id');
    }

    public function reports()
    {
        return $this->hasMany(Report::class, 'health_facility_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name);
            }
        });
    }
}
