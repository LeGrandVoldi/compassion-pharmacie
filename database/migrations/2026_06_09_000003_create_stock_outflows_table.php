<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_outflows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_unit_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity');
            $table->string('reason', 255);
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['product_id', 'product_unit_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_outflows');
    }
};
