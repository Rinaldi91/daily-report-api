<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    protected $table = 'reports';

    protected $fillable = [
        'employee_id',
        'health_facility_id',
        'report_date',
        'report_number',
        'problem',
        'error_code',
        'job_action',
        'customer',
        'position_customer',
        'phone_customer',
        'type_of_visit',
        'progress',
        'brand_type_product',
        'constraint',
        'information_update',
        'attendance',
        'start_time',
        'end_time',
        'total_time',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function healthFacility()
    {
        return $this->belongsTo(HealthFacility::class, 'health_facility_id');
    }

    public function location()
    {
        return $this->hasMany(Location::class, 'report_id');
    }

    public function reportDeviceItem()
    {
        return $this->hasMany(ReportDeviceItem::class, 'report_id');
    }
}
