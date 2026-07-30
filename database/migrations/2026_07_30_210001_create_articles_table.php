<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('unit', 8)->default('kom');
            $table->string('tax_label', 4)->nullable();   // oznaka sa uređaja: F, P, A…
            $table->string('gtin', 14)->nullable();       // barkod — v1 ga nije imao pa je slao izmišljen
            $table->integer('last_unit_price')->nullable(); // pfening
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
