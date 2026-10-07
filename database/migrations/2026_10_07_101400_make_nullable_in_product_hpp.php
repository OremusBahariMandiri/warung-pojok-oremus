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
        Schema::table('product_hpp', function (Blueprint $table) {
            $table->decimal("selling_price", 15, 3)->nullable()->change();
            $table->enum("hpp_method", ['MANUAL', 'CALCULATED'])->nullable()->change();
            $table->decimal("current_hpp", 15, 3)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_hpp', function (Blueprint $table) {
            $table->decimal("selling_price", 15, 3)->nullable(false)->change();
            $table->enum("hpp_method", ['MANUAL', 'CALCULATED'])->nullable(false)->change();
            $table->decimal("current_hpp", 15, 3)->nullable(false)->change();
        });
    }
};