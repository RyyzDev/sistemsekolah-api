<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('nip', 20)->unique(); // Nomor Induk Pegawai
            $table->date('birth_date');
            $table->string('birth_place', 100);
            $table->enum('gender', ['L', 'P']);
            $table->text('address')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('specialization', 100)->nullable(); // Bidang keahlian
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('nip');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teachers');
    }
};