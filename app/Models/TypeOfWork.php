<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TypeOfWork extends Model
{
    use HasFactory;

    protected  $table = "type_of_works";

    protected $fillable = [
        "name",
        "slug",
        "description"
    ];

    public function reportDeviceItem()
    {
        return $this->belongsToMany(ReportDeviceItem::class, 'device_work_type');
    }

    public function deviceWorkType()
    {
        return $this->hasMany(DeviceWorkType::class);
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
