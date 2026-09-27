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
        Schema::create('product_hpp_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId("product_hpp_id")->constrained("product_hpp")->references("id")->onUpdate("restrict")->onDelete("cascade");
            $table->foreignId("hpp_id")->constrained("hpp")->references("id")->onUpdate("restrict")->onDelete("cascade");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_hpp_detail');
    }
};