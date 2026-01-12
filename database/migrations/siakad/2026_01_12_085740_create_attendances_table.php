<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('classroom_id')->constrained('classrooms')->onDelete('cascade');
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->onDelete('set null');
            $table->foreignId('teacher_id')->constrained('teachers')->onDelete('cascade');
            $table->date('date');
            $table->enum('status', ['H', 'I', 'S', 'A']); // Hadir, Izin, Sakit, Alpha
            $table->text('notes')->nullable();
            $table->boolean('notification_sent')->default(false);
            $table->timestamps();
            
            $table->unique(['student_id', 'classroom_id', 'subject_id', 'date']);
            $table->index(['date', 'status']);
            $table->index('student_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};