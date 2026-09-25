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
        Schema::create('restock_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId("restock_id")->constrained("restock")->onUpdate("restrict")->onDelete("cascade");
            $table->foreignId("product_id")->constrained("products")->onUpdate("restrict")->onDelete("cascade");
            $table->foreignId("restock_unit_id")->constrained("units")->onUpdate("restrict")->onDelete("cascade");
            $table->integer("quantity");
            $table->decimal("purchase_price", 15, 3);
            $table->decimal('total_price', 15, 3);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('restock_items');
    }
};