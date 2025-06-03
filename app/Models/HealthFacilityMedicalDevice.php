<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HealthFacilityMedicalDevice extends Model
{
    use HasFactory;

    protected $table = "health_facility_medical_devices";

    protected $fillable = [
        'health_facility_id',
        'medical_device_id',
    ];

    public function healthFacility()
    {
        return $this->belongsTo(HealthFacility::class, 'health_facility_id');
    }

    
}
