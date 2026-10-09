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
        Schema::create('offline_logs', function (Blueprint $table) {
            $table->id();
            $table->string('nama_pengurus');
            $table->string('kode_akses');
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->unsignedInteger('no_antrian');
            $table->string('no_tps')->nullable();
            $table->string('voting_status')->default('antrean'); // antrean, sudah_memilih, tidak_memilih, cancel_vote
            $table->timestamps();

            $table->index('no_antrian');
            $table->index('voting_status');
            $table->index('member_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offline_logs');
    }
};
