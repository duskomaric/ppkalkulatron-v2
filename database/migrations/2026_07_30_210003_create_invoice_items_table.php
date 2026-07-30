<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('article_id')->nullable()->constrained()->nullOnDelete();

            // Snapshot artikla u trenutku izdavanja — kasnija izmjena artikla ne mijenja račun.
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('unit', 8)->default('kom');
            $table->string('tax_label', 4)->nullable();

            $table->integer('quantity')->default(1);
            $table->integer('unit_price')->default(0);   // sa porezom, pfening
            $table->integer('tax_rate')->default(0);     // bazni poeni: 1100 = 11%
            $table->integer('subtotal')->default(0);
            $table->integer('tax_amount')->default(0);
            $table->integer('total')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
