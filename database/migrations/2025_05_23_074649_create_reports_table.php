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
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained();
            $table->foreignId('health_facility_id')->constrained();
            $table->date('report_date')->nullable();
            $table->string('report_number');
            $table->text('problem');
            $table->text('error_code');
            $table->text('job_action');
            $table->string('customer')->nullable();
            $table->string('position_customer')->nullable();
            $table->string('phone_customer')->nullable();
            $table->enum('type_of_visit', ['Promosi Produk', 'Follow-up Promosi Produk','Folloe-up Tagihan', 'Follow-up Kontrak', 'Penawaran']);
            $table->text('progress')->nullable();
            $table->text('brand_type_product')->nullable();
            $table->text('constraint')->nullable();
            $table->text('information_update')->nullable();
            $table->string('attendance');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('total_time');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
