<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Parameter extends Model
{
    use HasFactory;

    protected $table = 'parameters';

    protected $fillable = [
        'report_id',
        'name',
        'uraian',
        'description',
    ];

    public function report(){
        return $this->belongsTo(Report::class, 'report_id');
    }
}
