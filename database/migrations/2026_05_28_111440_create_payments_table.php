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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('sale_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('partner_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('client_number')
                ->nullable();

            $table->decimal('amount', 12, 2);

            $table->enum('payment_method', [
                'cash',
                'mpesa',
                'orange'
            ]);

            $table->string('reference')
                ->nullable();

            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
