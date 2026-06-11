<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasColumn('sales', 'extra_expense_amount')) {
                $table->decimal('extra_expense_amount', 12, 2)->default(0)->after('status');
            }

            if (!Schema::hasColumn('sales', 'extra_expense_description')) {
                $table->string('extra_expense_description', 255)->nullable()->after('extra_expense_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (Schema::hasColumn('sales', 'extra_expense_description')) {
                $table->dropColumn('extra_expense_description');
            }
            if (Schema::hasColumn('sales', 'extra_expense_amount')) {
                $table->dropColumn('extra_expense_amount');
            }
        });
    }
};
