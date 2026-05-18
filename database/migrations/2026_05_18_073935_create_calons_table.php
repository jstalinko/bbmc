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
        Schema::create('calons', function (Blueprint $table) {
            $table->id();
            $table->integer('member_id');
            $table->string('no_kartu');
            $table->string('chapter');
            $table->text('visi')->nullable();
            $table->text('misi')->nullable();
            $table->string('status')->default('diajukan'); // mengajukan, diajukan, ditetapkan, ditolak.
            $table->string('diajukan_oleh')->nullable(); // self atau member id
            $table->string('no_kartu_diajukan_oleh')->nullable(); // no kartu member yang mengajukan
            $table->string('foto_calon')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calons');
    }
};
