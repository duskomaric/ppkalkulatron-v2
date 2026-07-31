<?php

use App\Enums\InvoiceStatus;
use App\Models\Article;
use App\Models\Client;
use App\Models\Invoice;
use App\Services\InvoiceNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function invoicePayload(array $overrides = []): array
{
    return $overrides + [
        'client_id' => Client::create(['name' => 'Kupac d.o.o.'])->id,
        'payment_type' => 'Cash',
        'currency' => 'BAM',
        'template' => 'classic',
        'date' => now()->format('Y-m-d'),
        'due_date' => now()->addDays(15)->format('Y-m-d'),
        'items' => [[
            'article_id' => null,
            'name' => 'Usluga',
            'unit' => 'kom',
            'tax_label' => 'F',   // 11%
            'quantity' => 2,
            'unit_price' => '55.50',
        ]],
    ];
}

it('kreira račun i preračuna iznose iz količine i cijene', function () {
    $this->post(route('invoices.store'), invoicePayload())->assertRedirect();

    $invoice = Invoice::with('items')->firstOrFail();

    // 2 × 55,50 = 111,00 sa porezom; osnovica = 111 / 1,11 = 100,00; porez = 11,00
    expect($invoice->total)->toBe(11100)
        ->and($invoice->subtotal)->toBe(10000)
        ->and($invoice->tax_total)->toBe(1100)
        ->and($invoice->items)->toHaveCount(1)
        ->and($invoice->items->first()->tax_rate)->toBe(1100);
});

it('ne vjeruje iznosima iz forme nego ih računa sam', function () {
    // Klijent šalje i "total" — mora biti ignorisan.
    $this->post(route('invoices.store'), invoicePayload(['total' => 999999, 'subtotal' => 999999]));

    expect(Invoice::first()->total)->toBe(11100);
});

it('dodjeljuje brojeve redom', function () {
    $this->post(route('invoices.store'), invoicePayload());
    $this->post(route('invoices.store'), invoicePayload());

    $year = date('Y');
    expect(Invoice::pluck('invoice_number')->all())->toBe(["0001/{$year}", "0002/{$year}"]);
});

it('oslobađa broj kad se račun obriše', function () {
    $year = date('Y');

    $this->post(route('invoices.store'), invoicePayload());
    $this->post(route('invoices.store'), invoicePayload());

    $this->delete(route('invoices.destroy', Invoice::where('invoice_number', "0002/{$year}")->first()));

    // Broj se izvodi iz računa, pa je 0002 opet slobodan — u v1 nije bio.
    expect(app(InvoiceNumber::class)->next())->toBe("0002/{$year}");
});

it('vraća se na početni broj kad nema računa', function () {
    $this->post(route('invoices.store'), invoicePayload());
    $this->delete(route('invoices.destroy', Invoice::first()));

    expect(app(InvoiceNumber::class)->next())->toBe('0001/'.date('Y'));
});

it('pamti zadnju cijenu na artiklu', function () {
    $article = Article::create(['name' => 'Usluga', 'unit' => 'sat', 'tax_label' => 'F']);

    $this->post(route('invoices.store'), invoicePayload([
        'items' => [[
            'article_id' => $article->id,
            'name' => 'Usluga',
            'unit' => 'sat',
            'tax_label' => 'F',
            'quantity' => 1,
            'unit_price' => '80.00',
        ]],
    ]));

    expect($article->fresh()->last_unit_price)->toBe(8000);
});

it('traži bar jednu stavku', function () {
    $this->post(route('invoices.store'), invoicePayload(['items' => []]))
        ->assertSessionHasErrors('items');
});

it('ne prima rok dospijeća prije datuma', function () {
    $this->post(route('invoices.store'), invoicePayload([
        'date' => now()->format('Y-m-d'),
        'due_date' => now()->subDay()->format('Y-m-d'),
    ]))->assertSessionHasErrors('due_date');
});

it('mijenja račun i preračuna iznose ponovo', function () {
    $this->post(route('invoices.store'), invoicePayload());
    $invoice = Invoice::first();

    $this->put(route('invoices.update', $invoice), invoicePayload([
        'items' => [[
            'article_id' => null,
            'name' => 'Druga usluga',
            'unit' => 'kom',
            'tax_label' => 'F',
            'quantity' => 1,
            'unit_price' => '11.10',
        ]],
    ]))->assertRedirect(route('invoices.show', $invoice));

    $invoice->refresh()->load('items');

    expect($invoice->items)->toHaveCount(1)
        ->and($invoice->items->first()->name)->toBe('Druga usluga')
        ->and($invoice->total)->toBe(1110);
});

it('ne dozvoljava izmjenu ni brisanje fiskalizovanog računa', function () {
    $this->post(route('invoices.store'), invoicePayload());
    $invoice = Invoice::first();
    $invoice->update(['status' => InvoiceStatus::Fiscalized]);

    $this->get(route('invoices.edit', $invoice))->assertRedirect(route('invoices.show', $invoice));
    $this->put(route('invoices.update', $invoice), invoicePayload())->assertRedirect(route('invoices.show', $invoice));
    $this->delete(route('invoices.destroy', $invoice))->assertRedirect(route('invoices.show', $invoice));

    expect(Invoice::count())->toBe(1);
});

it('pretražuje po broju i po klijentu', function () {
    $this->post(route('invoices.store'), invoicePayload([
        'client_id' => Client::create(['name' => 'Mermer Gradnja'])->id,
    ]));
    $this->post(route('invoices.store'), invoicePayload([
        'client_id' => Client::create(['name' => 'Kafe Bar'])->id,
    ]));

    $this->get(route('invoices.index', ['q' => 'Mermer']))
        ->assertStatus(200)
        ->assertSee('Mermer Gradnja')
        ->assertDontSee('Kafe Bar');

    $this->get(route('invoices.index', ['q' => '0002']))
        ->assertStatus(200)
        ->assertSee('Kafe Bar');
});

it('prikazuje listu i detalj', function () {
    $this->post(route('invoices.store'), invoicePayload());
    $invoice = Invoice::first();

    $this->get(route('invoices.index'))->assertStatus(200)->assertSee($invoice->invoice_number);
    $this->get(route('invoices.show', $invoice))->assertStatus(200)->assertSee('Usluga');
});

it('servira detalje računa kao dio drawera', function () {
    $this->post(route('invoices.store'), invoicePayload());
    $invoice = Invoice::firstOrFail();

    $partial = $this->get(route('invoices.show', [$invoice, 'partial' => 1]));

    $partial->assertStatus(200)
        ->assertSee($invoice->invoice_number)
        ->assertSee('Zatvori')
        ->assertDontSee('<!DOCTYPE html>', false);
});
