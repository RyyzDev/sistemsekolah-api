<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('semesters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained('academic_years')->onDelete('cascade');
            $table->enum('type', ['Ganjil', 'Genap']);
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(false);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['academic_year_id', 'is_active']);
            $table->unique(['academic_year_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('semesters');
    }
};