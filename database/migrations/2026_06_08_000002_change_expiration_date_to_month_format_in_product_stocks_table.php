<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('product_stocks')) {
            return;
        }

        // Convertir les anciennes dates complètes en format YYYY-MM
        DB::statement("UPDATE product_stocks SET expiration_date = DATE_FORMAT(expiration_date, '%Y-%m') WHERE expiration_date IS NOT NULL");

        // Passer le champ en chaîne courte pour stocker uniquement le mois et l'année
        DB::statement("ALTER TABLE product_stocks MODIFY expiration_date VARCHAR(7) NULL");
    }

    public function down(): void
    {
        if (!Schema::hasTable('product_stocks')) {
            return;
        }

        // Revenir vers une date complète en prenant le 1er jour du mois
        DB::statement("UPDATE product_stocks SET expiration_date = CONCAT(expiration_date, '-01') WHERE expiration_date IS NOT NULL AND expiration_date REGEXP '^[0-9]{4}-[0-9]{2}$'");

        DB::statement("ALTER TABLE product_stocks MODIFY expiration_date DATE NULL");
    }
};
