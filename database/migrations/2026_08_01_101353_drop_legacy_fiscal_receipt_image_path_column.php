<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('fiscal_records', 'fiscal_receipt_image_path')) {
            return;
        }

        Schema::table('fiscal_records', function (Blueprint $table): void {
            $table->dropColumn('fiscal_receipt_image_path');
        });
    }

    public function down(): void
    {
        Schema::table('fiscal_records', function (Blueprint $table): void {
            $table->string('fiscal_receipt_image_path')->nullable();
        });
    }
};
