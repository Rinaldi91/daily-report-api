<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicalDevice extends Model
{

    use HasFactory;

    protected $fillable = [
        'medical_device_category_id',
        'brand',
        'model',
        'serial_number',
        'software_version',
        'status',
        'notes',
    ];

    public function medicalDeviceCategory()
    {
        return $this->belongsTo(MedicalDeviceCategory::class, 'medical_device_category_id');
    }

    public function reportDeviceItem()
    {
        return $this->hasMany(ReportDeviceItem::class, 'medical_device_id');
    }


}
