<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;

    protected $table = 'locations';

    protected $fillable = [
        'report_id',
        'latitude',
        'longitude',
        'address',
    ];

    public function report()
    {
        return $this->belongsTo(Report::class, 'report_id');
    }
}
