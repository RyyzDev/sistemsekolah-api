<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique(); // e.g., "MAT-10"
            $table->string('name', 100); // e.g., "Matematika"
            $table->string('grade_level', 10); // e.g., "10"
            $table->decimal('kkm', 5, 2)->default(75.00); // Kriteria Ketuntasan Minimal
            $table->json('grade_weights')->nullable(); // {"tugas": 20, "uh": 30, "uts": 20, "uas": 30}
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('grade_level');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subjects');
    }
};