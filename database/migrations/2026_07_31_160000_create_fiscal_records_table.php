<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fiscal_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('type', 16); // original, copy, refund
            $table->string('fiscal_invoice_number')->nullable();
            $table->string('fiscal_counter', 64)->nullable();
            $table->string('request_id', 64)->nullable();
            $table->text('verification_url')->nullable();
            $table->timestamp('fiscalized_at')->nullable();
            $table->timestamps();

            $table->index(['invoice_id', 'type']);
        });

        /**
         * Račun koji je uređaj vratio stoji u svojoj tabeli, a ne kao kolona na
         * fiscal_records: liste računa učitavaju fiskalne zapise, pa bi se slika
         * od nekoliko desetina kilobajta čitala pri svakom takvom upitu.
         *
         * Čuva se base64, ne binarno: MySQL `blob` staje na 64 KB (računi ovdje
         * dostižu 102 KB), dok se `longText` isto ponaša i na SQLite-u na uređaju.
         */
        Schema::create('fiscal_receipt_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fiscal_record_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('extension', 8)->default('png'); // png | pdf | html
            $table->longText('contents');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fiscal_receipt_images');
        Schema::dropIfExists('fiscal_records');
    }
};
