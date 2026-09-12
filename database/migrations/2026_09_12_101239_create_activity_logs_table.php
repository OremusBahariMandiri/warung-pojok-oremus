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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId("user_id")->constrained("users")->onUpdate("restrict")->onDelete("cascade");
            $table->string("action", 150);
            $table->string("module", 150);
            $table->string("entity_type", 180);
            $table->integer("entity_id");
            $table->text("decription");
            $table->mediumText("old_values");
            $table->mediumText("new_values");
            $table->ipAddress("ip_address");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};