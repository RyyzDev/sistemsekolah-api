<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('grades_ppdb', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            
            $table->enum('semester', ['1', '2', '3', '4', '5', '6']);
            $table->string('subject')->comment('Mata pelajaran');
            $table->decimal('score', 5, 2);
            $table->enum('grade_type', ['pengetahuan', 'keterampilan', 'rapor', 'us', 'un'])->default('rapor');
            $table->text('notes')->nullable();
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('grades_ppdb');
    }
};