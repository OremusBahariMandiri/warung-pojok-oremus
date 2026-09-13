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
        if (Schema::hasTable('activity_logs') && Schema::hasColumn('activity_logs', 'decription')) {
            Schema::table('activity_logs', function (Blueprint $table) {
                $table->renameColumn('decription', 'description');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('activity_logs') && Schema::hasColumn('activity_logs', 'description')) {
            Schema::table('activity_logs', function (Blueprint $table) {
                $table->renameColumn('description', 'decription');
            });
        }
    }
};
