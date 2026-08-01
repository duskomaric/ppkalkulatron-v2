<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('fiscal_receipt_images', function (Blueprint $table) {
            $table->string('path')->nullable()->unique();
            $table->string('checksum', 64)->nullable();
            $table->unsignedInteger('size')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fiscal_receipt_images', function (Blueprint $table) {
            $table->dropUnique(['path']);
            $table->dropColumn(['path', 'checksum', 'size']);
        });
    }
};
