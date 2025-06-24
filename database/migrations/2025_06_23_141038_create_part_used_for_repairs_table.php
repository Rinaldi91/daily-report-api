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
        Schema::create('part_used_for_repairs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained();
            $table->text('uraian')->nullable();
            $table->string('qiantity')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('part_used_for_repairs');
    }
};
