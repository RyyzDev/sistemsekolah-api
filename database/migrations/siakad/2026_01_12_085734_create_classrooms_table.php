<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classrooms', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50); // e.g., "X IPA 1"
            $table->string('grade_level', 10); // e.g., "10", "11", "12"
            $table->foreignId('academic_year_id')->constrained('academic_years')->onDelete('cascade');
            $table->foreignId('homeroom_teacher_id')->nullable()->constrained('users')->onDelete('set null');
            $table->integer('capacity')->default(30);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['academic_year_id', 'grade_level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classrooms');
    }
};