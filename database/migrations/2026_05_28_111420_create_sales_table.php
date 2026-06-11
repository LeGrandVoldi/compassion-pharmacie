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
        Schema::create('sales', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('client_number')
                ->nullable();

            $table->foreignId('partner_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->decimal('total_amount', 12, 2);

            $table->enum('payment_type', [
                'cash',
                'mobile_money',
                'partenaire'
            ]);

            $table->enum('status', [
                'paid',
                'unpaid'
            ]);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
