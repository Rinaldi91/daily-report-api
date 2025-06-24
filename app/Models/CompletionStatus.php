<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str; 

class CompletionStatus extends Model
{
    use HasFactory;

    protected $table = 'completion_statuses';

    protected $fillable = [
        "name",
        "slug",
        "description"
    ];

    public function reportDeviceItem()
    {
        return $this->hasMany(ReportDeviceItem::class);
    }

    public function reportDetail(){
        return $this->hasMany(ReportDetail::class, 'completion_status_id');
    }

    protected static function boot(){
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name);
            }
        });
    }

}
