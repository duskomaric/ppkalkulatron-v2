<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indeksi koje SQLite na uređaju nije dobio sam.
 *
 * `foreignId()->constrained()` na MySQL-u dobije indeks uz strani ključ, jer ga
 * InnoDB pravi sam. SQLite to ne radi — strani ključ tamo ostaje bez indeksa. Na
 * telefonu su zato `invoices.refund_invoice_id`, `invoices.client_id` i
 * `invoice_items.invoice_id` čitani punim prolazom kroz tabelu, a prvi pri svakom
 * otvaranju liste računa (`with('originalInvoice')`).
 *
 * Nazivi klijenata i artikala se sortiraju na svakoj strani šifarnika
 * (`orderBy('name')`), što je bez indeksa značilo prolaz kroz tabelu plus
 * privremeni B-tree za sortiranje.
 *
 * Nova migracija, a ne izmjena postojećih: te su već pokrenute na uređajima.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->index('name');
        });

        Schema::table('articles', function (Blueprint $table) {
            $table->index('name');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->index('client_id');
            $table->index('refund_invoice_id');
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->index('invoice_id');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropIndex(['name']);
        });

        Schema::table('articles', function (Blueprint $table) {
            $table->dropIndex(['name']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['client_id']);
            $table->dropIndex(['refund_invoice_id']);
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropIndex(['invoice_id']);
        });
    }
};
