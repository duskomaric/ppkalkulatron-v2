<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Račun preuzet sa fiskalne kase: kupca i artikle kasa ne zna u cjelini,
            // pa se takav račun označava da korisnik zna šta još treba dopuniti.
            $table->timestamp('imported_at')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('imported_at');
        });
    }
};
