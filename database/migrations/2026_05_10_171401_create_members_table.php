<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('nama_lengkap');
            $table->string('nama_panggilan');
            $table->string('tempat_lahir');
            $table->date('tanggal_lahir');
            $table->string('jenis_kelamin');
            $table->string('gol_darah');
            $table->string('nik');
            $table->string('alamat');
            $table->string('no_wa');
            $table->string('email')->nullable();
            $table->string('profesi')->nullable();
            $table->string('foto')->nullable();
            $table->string('no_kartu')->nullable();
            $table->string('status_keanggotaan');
            $table->string('chapter');
            $table->string('checkpoint');
            $table->date('terdaftar_sejak')->nullable();
            $table->string('jenis_motor')->nullable();
            $table->string('tahun_motor')->nullable();
            $table->string('no_pol')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
