<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('branch_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_variant_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity')->default(0);
            $table->unsignedInteger('reorder_level')->default(0);
            $table->timestamps();
            $table->unique(['branch_id', 'product_variant_id']);
            $table->index(['branch_id', 'quantity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_stocks');
    }
};
