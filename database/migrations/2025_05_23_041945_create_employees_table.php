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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('region_id')->constrained();
            $table->foreignId('division_id')->constrained();
            $table->foreignId('position_id')->constrained();
            $table->string('employee_number')->unique();
            $table->string('nik');
            $table->string('name');
            $table->enum('gender',['Laki - Laki', 'Perempuan']);
            $table->string('place_of_birth');
            $table->date('date_of_birth');
            $table->string('phone_number');
            $table->string('email');
            $table->string('address');
            $table->enum('status', ['Training', 'Karyawan Kontrak', 'Karyawan Tetap']);
            $table->date('date_of_entry');
            $table->boolean('is_active');
            $table->string('photo');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
