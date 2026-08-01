<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Prateće tabele iz v1, bez company_id — aplikacija nije multi-tenant.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 3)->unique();
            $table->string('name');
            $table->string('symbol');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('bank_name');
            $table->string('account_number');
            $table->string('swift')->nullable();
            $table->boolean('show_on_documents')->default(true);
            $table->timestamps();
        });

        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->string('currency', 3)->unique();
            $table->decimal('rate_to_bam', 10, 5);
            $table->date('rate_date');
            $table->timestamps();
        });

        // Katalog puni isključivo fiskalni uređaj kroz /api/status.
        Schema::create('fiscal_tax_rates', function (Blueprint $table) {
            $table->id();
            $table->string('label', 16)->unique();
            $table->decimal('rate', 5, 2);
            $table->string('category_name', 120);
            $table->unsignedTinyInteger('category_type')->nullable();
            $table->timestamps();
        });

        DB::table('currencies')->insert([
            ['code' => 'BAM', 'name' => 'Konvertibilna marka', 'symbol' => 'KM', 'is_default' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'is_default' => false, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('fiscal_tax_rates');
        Schema::dropIfExists('exchange_rates');
        Schema::dropIfExists('bank_accounts');
        Schema::dropIfExists('currencies');
    }
};
