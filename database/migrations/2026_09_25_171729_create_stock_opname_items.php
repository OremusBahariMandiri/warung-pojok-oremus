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
        Schema::create('stock_opname_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId("opname_id")->constrained("stock_opname")->references("id")->onUpdate("restrict")->onDelete("cascade");
            $table->foreignId("product_id")->constrained("products")->references("id")->onUpdate("restrict")->onDelete("cascade");
            $table->integer("system_stock");
            $table->integer("physical_stock");
            $table->integer("difference");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_opname_items');
    }
};