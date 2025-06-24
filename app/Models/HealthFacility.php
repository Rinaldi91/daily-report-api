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
        'address',
        'lat',
        'lng'
    ];

    public function type()
    {
        return $this->belongsTo(TypeOfHealthFacility::class, 'type_of_health_facility_id');
    }

    public function reports()
    {
        return $this->hasMany(Report::class, 'health_facility_id');
    }

    public function reportDeviceItem()
    {
        return $this->hasMany(ReportDeviceItem::class, 'health_facility_id');
    }

    // Relasi many-to-many dengan medical devices melalui pivot table
    public function medicalDevices()
    {
        return $this->belongsToMany(
            MedicalDevice::class, 
            'health_facilities_medical_devices', 
            'health_facility_id', 
            'medical_device_id'
        )->withTimestamps();
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