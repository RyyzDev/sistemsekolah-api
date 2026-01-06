<?php
// database/migrations/xxxx_create_parents_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('parents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->enum('parent_type', ['ayah', 'ibu', 'wali']);
            
            // Data Pribadi
            $table->string('full_name');
            $table->string('nik', 16)->nullable();
            $table->string('birth_place')->nullable();
            $table->date('birth_date')->nullable();
            $table->enum('religion', ['islam', 'kristen', 'katolik', 'hindu', 'buddha', 'konghucu'])->nullable();
            $table->enum('citizenship', ['wni', 'wna'])->default('wni');
            
            // Pendidikan & Pekerjaan
            $table->enum('education', ['tidak_sekolah', 'sd', 'smp', 'sma', 'diploma', 's1', 's2', 's3'])->nullable();
            $table->string('occupation')->nullable();
            $table->enum('occupation_category', ['tidak_bekerja', 'pns', 'tni_polri', 'guru_dosen', 'pegawai_swasta', 'wiraswasta', 'petani', 'nelayan', 'buruh', 'pensiunan', 'lainnya'])->nullable();
            $table->decimal('monthly_income', 12, 2)->nullable();
            
            // Kontak
            $table->string('phone_number')->nullable();
            $table->string('mobile_number')->nullable();
            $table->string('email')->nullable();
            
            // Alamat
            $table->text('address')->nullable();
            $table->string('rt', 3)->nullable();
            $table->string('rw', 3)->nullable();
            $table->string('kelurahan')->nullable();
            $table->string('kecamatan')->nullable();
            $table->string('kabupaten_kota')->nullable();
            $table->string('province')->nullable();
            $table->string('postal_code', 5)->nullable();
            
            // Status
            $table->enum('living_status', ['hidup', 'meninggal'])->default('hidup');
            $table->boolean('is_guardian')->default(false);
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('parents');
    }
};