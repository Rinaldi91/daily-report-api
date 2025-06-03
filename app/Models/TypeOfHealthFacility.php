<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TypeOfHealthFacility extends Model
{
    use HasFactory;

    protected $table = 'type_of_health_facilities';

    protected $fillable = [
        'name',
        'slug',
        'description'
    ];

    public function healthFacility()
    {
        return $this->hasMany(HealthFacility::class, 'type_of_health_facility_id');
    }

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
