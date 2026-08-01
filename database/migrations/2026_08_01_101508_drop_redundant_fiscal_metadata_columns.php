<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $invoiceColumns = array_values(array_filter([
            'is_fiscalized',
            'fiscal_invoice_number',
            'fiscal_counter',
            'fiscal_verification_url',
            'fiscal_request_id',
            'fiscalized_at',
        ], fn (string $column): bool => Schema::hasColumn('invoices', $column)));

        if ($invoiceColumns !== []) {
            Schema::table('invoices', function (Blueprint $table) use ($invoiceColumns): void {
                $table->dropColumn($invoiceColumns);
            });
        }

        if (! Schema::hasColumn('fiscal_records', 'fiscal_meta')) {
            return;
        }

        Schema::table('fiscal_records', function (Blueprint $table): void {
            $table->dropColumn('fiscal_meta');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->boolean('is_fiscalized')->default(false);
            $table->string('fiscal_invoice_number')->nullable();
            $table->string('fiscal_counter', 64)->nullable();
            $table->text('fiscal_verification_url')->nullable();
            $table->string('fiscal_request_id', 32)->nullable();
            $table->timestamp('fiscalized_at')->nullable();
        });

        Schema::table('fiscal_records', function (Blueprint $table): void {
            $table->json('fiscal_meta')->nullable();
        });
    }
};
