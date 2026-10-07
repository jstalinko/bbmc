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
        if (Schema::hasTable('members') && !Schema::hasColumn('members', 'offline_voter')) {
            Schema::table('members', function (Blueprint $table) {
                $table->boolean('offline_voter')->default(false)->after('jabatan');
            });
        }

        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'offline_voter')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('offline_voter')->default(false);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('members') && Schema::hasColumn('members', 'offline_voter')) {
            Schema::table('members', function (Blueprint $table) {
                $table->dropColumn('offline_voter');
            });
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'offline_voter')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('offline_voter');
            });
        }
    }
};
