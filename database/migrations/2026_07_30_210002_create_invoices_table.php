<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Oblik prati v1, bez company_id — aplikacija nije multi-tenant.
     * Svi novčani iznosi su u pfeningu (integer), kao u v1.
     */
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 24)->default('created');
            $table->date('date');
            $table->date('due_date');
            $table->text('notes')->nullable();
            $table->string('currency', 3)->default('BAM');
            $table->string('payment_type', 32)->default('Cash');

            // Storno veza na originalni račun
            $table->foreignId('refund_invoice_id')->nullable()->constrained('invoices')->nullOnDelete();

            // Fiskalizacija
            $table->boolean('is_fiscalized')->default(false);
            $table->string('fiscal_invoice_number')->nullable();
            $table->string('fiscal_counter', 64)->nullable();
            $table->text('fiscal_verification_url')->nullable();
            $table->string('fiscal_request_id', 32)->nullable();
            $table->timestamp('fiscalized_at')->nullable();

            // Iznosi u pfeningu
            $table->integer('subtotal')->default(0);
            $table->integer('tax_total')->default(0);
            $table->integer('discount_total')->default(0);
            $table->integer('total')->default(0);

            $table->timestamps();

            $table->index('status');
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
