<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('fiscal_receipt_images', 'fiscal_receipts');
    }

    public function down(): void
    {
        Schema::rename('fiscal_receipts', 'fiscal_receipt_images');
    }
};
