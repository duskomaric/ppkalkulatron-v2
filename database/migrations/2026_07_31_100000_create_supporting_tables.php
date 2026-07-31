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

        // Oznake koje uređaj priznaje. Prave vrijednosti dolaze iz /api/status →
        // currentTaxRates; ovo su one koje je testna kasa javila.
        Schema::create('tax_rates', function (Blueprint $table) {
            $table->id();
            $table->string('label', 4)->unique();
            $table->unsignedTinyInteger('rate')->default(0);
            $table->string('category_name', 32)->nullable();
            $table->timestamps();
        });

        DB::table('tax_rates')->insert(collect([
            ['F', 11, 'ECAL'], ['N', 0, 'N-TAX'], ['P', 40, 'PBL'], ['E', 6, 'STT'],
            ['T', 2, 'TOTL'], ['A', 9, 'VAT'], ['B', 0, 'VAT'], ['C', 0, 'VAT-EXCL'],
        ])->map(fn ($r) => [
            'label' => $r[0], 'rate' => $r[1], 'category_name' => $r[2],
            'created_at' => now(), 'updated_at' => now(),
        ])->all());

        DB::table('currencies')->insert([
            ['code' => 'BAM', 'name' => 'Konvertibilna marka', 'symbol' => 'KM', 'is_default' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'is_default' => false, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_rates');
        Schema::dropIfExists('exchange_rates');
        Schema::dropIfExists('bank_accounts');
        Schema::dropIfExists('currencies');
    }
};
