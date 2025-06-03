<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class MedicalDeviceCategory extends Model
{
    use HasFactory;

    protected $table = 'medical_device_categories';

    protected $fillable = [
        'name',
        'slug',
        'description'
    ];

    public function medicalDevice()
    {
        return $this->hasMany(MedicalDevice::class, 'medical_device_category_id');
    }

    // Auto generate slug before saving
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name);
            }
        });
    }
}
