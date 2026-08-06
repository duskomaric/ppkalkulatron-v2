<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kurs traži više decimala nego što je kolona nosila.
     *
     * Centralna banka objavljuje kurs za 100 jedinica kod jena, forinte i dinara,
     * pa kurs za jednu jedinicu ide na osam decimala; na pet je zaokruživanje
     * mijenjalo iznos u KM koji ide fiskalnoj kasi.
     */
    public function up(): void
    {
        Schema::table('exchange_rates', function (Blueprint $table) {
            $table->decimal('rate_to_bam', 18, 8)->change();
        });
    }

    public function down(): void
    {
        Schema::table('exchange_rates', function (Blueprint $table) {
            $table->decimal('rate_to_bam', 10, 5)->change();
        });
    }
};
