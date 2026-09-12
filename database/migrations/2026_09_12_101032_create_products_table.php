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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string("prod_name");
            $table->string("slug");
            $table->string("sku");
            $table->string("satuan");
            $table->decimal("selling_price", 15, 3);
            $table->decimal("unit_price", 15, 3);
            $table->enum("hpp_method", ['manual', 'calculated']);
            $table->decimal("current_hpp", 15, 3);
            $table->integer("current_stock");
            $table->integer("min_stock");
            $table->text("description");
            $table->string("thumbnail", 255);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};