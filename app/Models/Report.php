<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

     protected $table = "reports";

    protected $fillable = [
        'employee_id', 
        'user_id', 
        'health_facility_id', 
        'report_number', 
        'problem',
        'error_code', 
        'job_action', 
        'note', 
        'suggestion',
        'attendance_employee',
        'attendance_customer',
        'is_status',
        'completed_at',
        'total_time'
    ];

    public function employee(){
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function user(){
        return $this->belongsTo(User::class, 'user_id');
    }

    public function healthFacility(){
        return $this->belongsTo(HealthFacility::class, 'health_facility_id');
    }

    public function reportDeviceItem(){
        return $this->hasMany(ReportDeviceItem::class, 'report_id');
    }

    public function parameter(){
        return $this->hasMany(Parameter::class, 'report_id');
    }

    public function reportDetail(){
        return $this->hasMany(ReportDetail::class, 'report_id');
    }

    public function partUsedForRepair(){
        return $this->hasMany(PartUsedForRepair::class, 'report_id');
    }

    public function location(){
        return $this->hasOne(Location::class, 'report_id');
    }

    
}
