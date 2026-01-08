<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            
            $table->enum('document_type', [
                'akta_kelahiran',
                'kartu_keluarga',
                'ijazah',
                'skhun',
                'rapor',
                'foto',
                'ktp_orangtua',
                'kip',
                'kks',
                'pkh',
                'surat_keterangan_domisili',
                'surat_keterangan_tidak_mampu',
                'surat_keterangan_disabilitas',
                'surat_pindah',
                'lainnya'
            ]);
            $table->string('document_name');
            $table->string('file_path');
            $table->string('file_type')->nullable();
            $table->integer('file_size')->nullable()->comment('bytes');
            $table->text('description')->nullable();
            $table->boolean('is_required')->default(true);
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->string('verified_by')->nullable();
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('documents');
    }
};