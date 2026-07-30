<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Postavke same aplikacije na uređaju — ono što ne pripada ni kompaniji ni korisniku.
     *
     * Zasad drži PIN zaključavanje. PIN se čuva kao hash, nikad kao tekst, a brojač
     * neuspjelih pokušaja je u bazi a ne u sesiji, da se zaključavanje ne resetuje
     * gašenjem aplikacije.
     */
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
