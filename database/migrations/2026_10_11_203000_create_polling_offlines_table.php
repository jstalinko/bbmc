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
        Schema::create('polling_offlines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('calon_id')->nullable()->constrained('calons')->nullOnDelete();
            $table->unsignedInteger('jumlah_suara');
            $table->string('penanggung_jawab');
            $table->string('foto_bukti')->nullable();
            $table->text('keterangan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('polling_offlines');
    }
};
