<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->foreignId('semester_id')->constrained('semesters')->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained('teachers')->onDelete('cascade');
            $table->foreignId('grade_component_id')->constrained('grade_components')->onDelete('cascade');
            $table->decimal('score', 5, 2); // e.g., 85.50
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->unique(['student_id', 'subject_id', 'semester_id', 'grade_component_id'], 'unique_grade_entry');
            $table->index(['student_id', 'semester_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grades');
    }
};