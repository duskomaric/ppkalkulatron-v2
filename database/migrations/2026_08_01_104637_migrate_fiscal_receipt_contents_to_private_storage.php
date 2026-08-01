<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('fiscal_receipt_images')->orderBy('id')->eachById(function (object $receipt): void {
            $binary = base64_decode($receipt->contents, true);

            if ($binary === false || $binary === '') {
                throw new RuntimeException("Fiskalni dokument {$receipt->id} nema ispravan sadržaj.");
            }

            $path = 'fiscal-receipts/'.$receipt->fiscal_record_id.'.'.strtolower($receipt->extension);

            if (! Storage::disk('local')->put($path, $binary)) {
                throw new RuntimeException("Fiskalni dokument {$receipt->id} nije moguće sačuvati.");
            }

            DB::table('fiscal_receipt_images')->where('id', $receipt->id)->update([
                'path' => $path,
                'checksum' => hash('sha256', $binary),
                'size' => strlen($binary),
            ]);
        });
    }

    public function down(): void {}
};
