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
        Schema::create('restock', function (Blueprint $table) {
            $table->id();
            $table->foreignId("created_by")->constrained("users")->onUpdate("restrict")->onDelete("cascade");
            $table->string("restock_code");
            $table->string("invoice_number");
            $table->timestamp("restock_date");
            $table->string("supplier_name", 180);
            $table->enum("status_restock", ['DRAFT', 'CONFIRMED']);
            $table->decimal("subtotal", 15, 3);
            $table->decimal("discount", 15,3);
            $table->decimal("grand_total", 15,3);
            $table->text("notes")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('restock');
    }
};