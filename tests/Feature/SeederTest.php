<?php

use App\Enums\InvoiceStatus;
use App\Models\FiscalTaxRate;
use App\Models\Invoice;
use App\Settings\FiscalSettings;
use Database\Seeders\DatabaseSeeder;

it('seeder puni bazu testnim podacima za kasu', function (): void {
    FiscalTaxRate::query()->delete();

    $this->seed(DatabaseSeeder::class);

    $fiscalized = Invoice::whereHas('fiscalRecords')->with('fiscalRecords', 'refundInvoice')->orderBy('id')->get();

    expect(FiscalTaxRate::count())->toBe(8)
        ->and(Invoice::count())->toBe(5)
        ->and($fiscalized)->toHaveCount(4)
        ->and(Invoice::whereNotNull('imported_at')->count())->toBe(1)
        ->and(Invoice::where('status', InvoiceStatus::Refunded)->count())->toBe(1)
        ->and(Invoice::whereNotNull('refund_invoice_id')->first()->status)->toBe(InvoiceStatus::Fiscalized)
        ->and(app(FiscalSettings::class)->base_url)->toBe('https://pos.ofs.ba');
});
