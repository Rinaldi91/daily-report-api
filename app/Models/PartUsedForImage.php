<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartUsedForImage extends Model
{
    use HasFactory;

    protected $table = 'part_used_for_repair_images';

    protected $fillable = [
        'part_used_for_id',
        'image',
    ];

    public function partUsedForRepair(){
        return $this->belongsTo(PartUsedForRepair::class, 'part_used_for_id');
    }

}
