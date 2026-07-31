<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kurs je jedinstven po valuti i datumu, ne po valuti.
     *
     * Prvobitni indeks je bio samo na valuti, što je dopuštalo tačno jedan kurs za
     * EUR ikada — a preračun traži kurs na datum računa, dakle istoriju.
     */
    public function up(): void
    {
        Schema::table('exchange_rates', function (Blueprint $table) {
            $table->dropUnique('exchange_rates_currency_unique');
            $table->unique(['currency', 'rate_date']);
        });
    }

    public function down(): void
    {
        Schema::table('exchange_rates', function (Blueprint $table) {
            $table->dropUnique(['currency', 'rate_date']);
            $table->unique('currency');
        });
    }
};
