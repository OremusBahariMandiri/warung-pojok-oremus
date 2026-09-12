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
        Schema::create('users_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users', 'id_user')->onUpdate('restrict')->onDelete('cascade');
            $table->string("menu_access");
            $table->string("index_acs");
            $table->string("show_acs");
            $table->string("create_acs");
            $table->string("edit_acs");
            $table->string("delete_acs");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users_access');
    }
};