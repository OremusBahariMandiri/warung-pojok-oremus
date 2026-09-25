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
            $table->foreignId('unit_id')->constrained('units')->references('id')->onDelete('cascade')->onUpdate('restrict');
            $table->string("prod_code");
            $table->string("prod_name");
            $table->string("slug");
            $table->enum("hpp_method", ['MANUAL', 'CALCULATED']);
            $table->integer("initial_stock")->default(0);
            // $table->decimal("current_hpp", 15, 3);
            $table->integer("current_stock")->default(0);
            $table->integer("min_stock");
            // $table->decimal("unit_price", 15, 3);
            $table->text("description")->nullable();
            $table->string("thumbnail", 255)->default('thumbnail/default.png');
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