<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('health_facilities_medical_devices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('health_facility_id');
            $table->unsignedBigInteger('medical_device_id');
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('health_facility_id')
                ->references('id')
                ->on('health_facilities')
                ->onDelete('cascade');

            $table->foreign('medical_device_id')
                ->references('id')
                ->on('medical_devices')
                ->onDelete('cascade');

            // Unique constraint untuk mencegah duplikasi
            $table->unique(['health_facility_id', 'medical_device_id'], 'hf_md_unique');

            // Index untuk performance
            $table->index('health_facility_id');
            $table->index('medical_device_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('health_facilities_medical_devices');
    }
};
