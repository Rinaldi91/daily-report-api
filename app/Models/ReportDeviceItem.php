<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportDeviceItem extends Model
{
    use HasFactory;

    protected $table = 'report_device_items';

    protected $fillable = [
        'report_id',
        'medical_device_id',
        'type_of_work_id',
    ];

    public function report(){
        return $this->belongsTo(Report::class, 'report_id');
    }
}
