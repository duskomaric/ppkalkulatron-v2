<?php

use App\Enums\FiscalRecordType;
use App\Enums\InvoiceStatus;
use App\Models\Article;
use App\Models\FiscalTaxRate;
use App\Services\FiscalReceiptStore;

it('povezuje stavku sa računom i artiklom uz ispravne castove', function (): void {
    $article = Article::create(['name' => 'Konsultacije', 'unit' => 'sat']);
    $invoice = makeInvoice();
    $item = $invoice->items()->create([
        'article_id' => $article->id,
        'name' => 'Konsultacije',
        'unit' => 'sat',
        'quantity' => '2',
        'unit_price' => '8055',
        'tax_rate' => '1100',
        'subtotal' => '14514',
        'tax_amount' => '1596',
        'total' => '16110',
    ]);

    expect($item->invoice->is($invoice))->toBeTrue()
        ->and($item->article->is($article))->toBeTrue()
        ->and($item->unit->value)->toBe('sat')
        ->and($item->quantity)->toBeInt()
        ->and($item->total)->toBeInt();
});

it('povezuje fiskalni zapis, sadržaj računa i njegov izvorni račun', function (): void {
    $invoice = makeInvoice();
    $record = $invoice->fiscalRecords()->create([
        'type' => FiscalRecordType::Original,
        'fiscal_invoice_number' => 'ABCD-1',
        'fiscalized_at' => now(),
    ]);
    app(FiscalReceiptStore::class)->store($record, 'fiskalni sadržaj', 'html');

    $receipt = $record->fresh('receipt')->receipt;

    expect($record->fresh()->invoice->is($invoice))->toBeTrue()
        ->and($record->fresh()->type)->toBe(FiscalRecordType::Original)
        ->and($receipt->fiscalRecord->is($record))->toBeTrue()
        ->and($receipt->extension)->toBe('html');
});

it('izvodi stope u baznim poenima za obračun računa', function (): void {
    FiscalTaxRate::query()->delete();
    $rate = FiscalTaxRate::factory()->create(['label' => 'F', 'rate' => 11, 'category_name' => 'Standardna']);
    FiscalTaxRate::factory()->create(['label' => 'E', 'rate' => 0, 'category_name' => 'Oslobođeno']);

    expect($rate->basisPoints())->toBe(1100)
        ->and(FiscalTaxRate::basisPointsByLabel())->toBe(['F' => 1100, 'E' => 0]);
});

it('čuva obje strane veze originalnog računa i storna', function (): void {
    $original = fiscalizedInvoice();
    $refund = refundFor($original);

    expect($original->fresh()->refundInvoice->is($refund))->toBeTrue()
        ->and($refund->fresh()->originalInvoice->is($original))->toBeTrue();
});

it('svaki status računa ima razumljivu oznaku, boju i pravilo brisanja', function (InvoiceStatus $status, string $label, string $color, bool $deletable): void {
    expect($status->label())->toBe($label)
        ->and($status->badgeColor())->toBe($color)
        ->and($status->canBeDeleted())->toBe($deletable);
})->with([
    [InvoiceStatus::Created, 'Kreiran', 'gray', true],
    [InvoiceStatus::Fiscalized, 'Fiskalizovan', 'green', false],
    [InvoiceStatus::RefundCreated, 'Storno kreiran', 'amber', true],
    [InvoiceStatus::Refunded, 'Storniran', 'red', false],
]);
