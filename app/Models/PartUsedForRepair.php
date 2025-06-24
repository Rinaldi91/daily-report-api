<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartUsedForRepair extends Model
{
    use HasFactory;

    protected $table = 'part_used_for_repairs';

    protected $fillable = [
        'report_id',
        'uraian',
        'quantity',
    ];

    public function report()
    {
        return $this->belongsTo(Report::class, 'report_id');
    }

    public function partUsedForImage(){
        return $this->hasMany(PartUsedForImage::class, 'part_used_for_repair_id');
    }
}
