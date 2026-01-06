<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Registrasi
            $table->string('registration_number')->unique()->nullable();
            $table->enum('registration_type', ['baru', 'pindahan', 'kembali_bersekolah'])->default('baru');
            $table->enum('registration_path', ['domisili', 'prestasi', 'afirmasi', 'mutasi'])->nullable();
            $table->date('registration_date')->nullable();
            $table->enum('status', ['draft', 'submitted', 'verified', 'accepted', 'rejected'])->default('draft');
            
            // Data Pribadi
            $table->string('full_name');
            $table->string('nickname')->nullable();
            $table->string('nisn', 10)->unique()->nullable();
            $table->string('nik', 16)->unique();
            $table->string('no_kk', 16);
            $table->string('no_akta_lahir')->nullable();
            $table->enum('gender', ['L', 'P']);
            $table->string('birth_place');
            $table->date('birth_date');
            $table->enum('religion', ['islam', 'kristen', 'katolik', 'hindu', 'buddha', 'konghucu'])->default('islam');
            $table->enum('citizenship', ['wni', 'wna'])->default('wni');
            $table->string('nationality')->default('Indonesia');
            
            // Alamat
            $table->text('address');
            $table->string('rt', 3);
            $table->string('rw', 3);
            $table->string('dusun')->nullable();
            $table->string('kelurahan');
            $table->string('kecamatan');
            $table->string('kabupaten_kota');
            $table->string('province');
            $table->string('postal_code', 5);
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            
            // Data Tambahan
            $table->enum('residence_type', ['bersama_orangtua', 'wali', 'kost', 'asrama', 'panti_asuhan'])->default('bersama_orangtua');
            $table->enum('transportation', ['jalan_kaki', 'sepeda', 'motor', 'mobil_pribadi', 'antar_jemput', 'angkutan_umum'])->default('jalan_kaki');
            $table->string('phone_number')->nullable();
            $table->string('mobile_number')->nullable();
            $table->string('email')->nullable();
            
            // Kesehatan
            $table->integer('height')->nullable()->comment('cm');
            $table->integer('weight')->nullable()->comment('kg');
            $table->string('blood_type')->nullable();
            $table->enum('special_needs', ['tidak', 'tuna_netra', 'tuna_rungu', 'tuna_grahita', 'tuna_daksa', 'tuna_laras', 'lainnya'])->default('tidak');
            $table->text('special_needs_description')->nullable();
            $table->text('disease_history')->nullable();
            
            // Data Periodik
            $table->integer('child_number')->nullable();
            $table->integer('total_siblings')->nullable();
            $table->string('hobby')->nullable();
            $table->string('ambition')->nullable();
            
            // Bantuan Sosial
            $table->boolean('kps_pkh_recipient')->default(false);
            $table->string('kps_pkh_number')->nullable();
            $table->boolean('kip_recipient')->default(false);
            $table->string('kip_number')->nullable();
            $table->boolean('pip_eligible')->default(false);
            $table->string('kks_number')->nullable();
            
            // Sekolah Asal
            $table->string('previous_school_name')->nullable();
            $table->string('previous_school_npsn')->nullable();
            $table->text('previous_school_address')->nullable();
            $table->string('ijazah_number')->nullable();
            $table->date('ijazah_date')->nullable();
            $table->string('skhun_number')->nullable();
            
            // Foto
            $table->string('photo')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('students');
    }
};