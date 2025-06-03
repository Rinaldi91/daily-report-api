<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportDeviceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_id',
        'medical_device_id',
        'completion_status_id',
        'note',
    ];

    public function report()
    {
        return $this->belongsTo(Report::class);
    }

    public function completionStatus()
    {
        return $this->belongsTo(CompletionStatus::class);
    }

    public function medicalDevice()
    {
        return $this->belongsTo(MedicalDevice::class);
    }

    public function typeOfWork()
    {
        return $this->belongsToMany(TypeOfWork::class, 'device_work_type');
    }


}
