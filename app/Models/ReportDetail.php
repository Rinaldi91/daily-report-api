<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportDetail extends Model
{
    use HasFactory;

    protected $tabel = 'report_details';

    protected $fillable = [
        'report_id',
        'completion_status_id',
        'note',
        'suggestion',
        'customer_name',
        'customer_phone',
        'attendance_employee',
        'attendance_customer',
    ];

    public function report(){
        return $this->belongsTo(Report::class, 'report_id');
    }

    public function completionStatus(){
        return $this->belongsTo(CompletionStatus::class, 'completion_status_id');
    }
}
