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
        Schema::create('stock_opname', function (Blueprint $table) {
            $table->id();
            $table->foreignId("created_by")->constrained("users")->references("id")->onUpdate("restrict")->onDelete("cascade");
            $table->string("opname_code", 255);
            $table->timestamp("opname_date");
            $table->text("notes");
            $table->enum("status_opname", ['DRAFT', 'REVIEW', 'AWAITING CONFIRMATION', 'COMPLETED']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_opname');
    }
};