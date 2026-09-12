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
        Schema::create('report_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId("report_id")->constrained("reports")->onUpdate("restrict")->onDelete("cascade");
            $table->foreignId("product_id")->constrained("products")->onUpdate("restrict")->onDelete("cascade");
            $table->integer("quantity");
            $table->decimal("selling_price", 15, 3);
            $table->decimal("hpp", 15, 3);
            $table->decimal("total_price",15,3);
            $table->decimal("total_hpp",15,3);
            $table->decimal("margin",15,3);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_details');
    }
};