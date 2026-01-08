<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            
            $table->string('achievement_name');
            $table->enum('achievement_type', ['akademik', 'non_akademik', 'organisasi', 'olahraga', 'seni', 'lainnya']);
            $table->enum('level', ['sekolah', 'kecamatan', 'kabupaten', 'provinsi', 'nasional', 'internasional']);
            $table->enum('rank', ['juara_1', 'juara_2', 'juara_3', 'finalis', 'peserta']);
            $table->string('organizer');
            $table->date('achievement_date');
            $table->year('year');
            $table->text('description')->nullable();
            $table->string('certificate_file')->nullable();
            $table->integer('points')->default(0)->comment('Poin untuk jalur prestasi');
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('achievements');
    }
};