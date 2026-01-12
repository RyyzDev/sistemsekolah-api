<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grade_components', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50); // e.g., "Tugas", "UH", "UTS", "UAS"
            $table->string('code', 20)->unique(); // e.g., "tugas", "uh", "uts", "uas"
            $table->integer('default_weight')->default(25); // Default percentage
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_components');
    }
};