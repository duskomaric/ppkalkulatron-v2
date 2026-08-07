<?php

use App\Enums\DocumentTemplate;
use App\Models\Client;
use App\Models\Invoice;
use App\Services\FiscalService;
use App\Services\InvoicePdfService;
use App\Services\InvoiceWriter;
use App\Services\PinLock;
use App\Services\SetupProgress;
use App\Settings\CompanySettings;
use App\Settings\FiscalSettings;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Native\Mobile\Runtime;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

// LazilyRefreshDatabase, ne RefreshDatabase: šema se migrira samo kad treba.
// Ovdje umjesto `uses()` u svakom fajlu — svaki Feature test radi nad čistom bazom.
pest()->extend(TestCase::class)
    ->use(LazilyRefreshDatabase::class)
    ->in('Feature');

beforeEach(function (): void {
    Storage::fake('local');
});

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/** Podaci forme računa: 2 × 55,50 sa oznakom F (11%). */
function invoicePayload(array $overrides = []): array
{
    return $overrides + [
        'client_id' => Client::create(['name' => 'Kupac d.o.o.'])->id,
        'payment_type' => 'Cash',
        'currency' => 'BAM',
        'language' => 'sr_Latn',
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

/** Račun sa jednom stavkom od 1,00 KM, upisan kroz InvoiceWriter. */
function makeInvoice(array $client = []): Invoice
{
    return app(InvoiceWriter::class)->create([
        'client_id' => Client::create(['name' => 'Kupac d.o.o.'] + $client)->id,
        'payment_type' => 'Cash',
        'currency' => 'BAM',
        'language' => 'sr_Latn',
        'date' => now()->format('Y-m-d'),
        'due_date' => now()->addDay()->format('Y-m-d'),
        'items' => [[
            'article_id' => null, 'name' => 'Usluga', 'unit' => 'kom',
            'tax_label' => 'F', 'quantity' => 1, 'unit_price' => '1.00',
        ]],
    ]);
}

/** Fiskalni uređaj koji prihvata svaki račun. */
function fakeDevice(array $extra = []): void
{
    Http::fake(['*/api/invoices' => Http::response([
        'invoiceNumber' => 'ABC12345-ABC12345-1',
        'invoiceCounter' => '1/1ПП',
        'verificationUrl' => 'https://example.test/v/?vl=x',
        'invoiceImagePngBase64' => base64_encode('slika-racuna'),
    ] + $extra)]);
}

/** Fiskalizovan račun; uređaj je već lažiran. */
function fiscalizedInvoice(array $client = []): Invoice
{
    fakeDevice();

    $invoice = makeInvoice($client);
    app(FiscalService::class)->fiscalize($invoice);

    return $invoice->fresh();
}

/** Storno napravljen onako kako ga pravi ekran računa — preko rute. */
function refundFor(Invoice $invoice): Invoice
{
    // test() vraća tekući TestCase; pestphp/pest-plugin-laravel nije instaliran,
    // pa globalne funkcije poput postJson() ne postoje.
    test()->postJson(route('invoices.create-refund', $invoice->fresh()))->assertSuccessful();

    return Invoice::latest('id')->firstOrFail();
}

/** Veleprodaja u fiskalnim podešavanjima. */
/**
 * Sklanja vodič za početno podešavanje sa liste računa.
 *
 * Svježa baza u testu nema ni firmu ni kasu, pa bi umjesto liste stajali koraci.
 */
function skipSetupGuide(): void
{
    app(SetupProgress::class)->dismiss();
}

function enableWholesale(): void
{
    $settings = app(FiscalSettings::class);
    $settings->wholesale = true;
    $settings->save();
}

/**
 * PIN je opcionalan: prvi put nije podešen i ulazi se direktno u račune.
 * Kad se podesi, traži se pri pokretanju. Ništa više od toga.
 */
function setPin(string $pin = '1111'): void
{
    app(PinLock::class)->set($pin);
}

/** Sesija otključane aplikacije u tekućem pokretanju procesa. */
function unlocked(): TestCase
{
    return test()->withSession([PinLock::SESSION_KEY => true, PinLock::BOOT_KEY => PinLock::boot()]);
}

/** Simulira trajni NativePHP proces, u kojem oznaka pokretanja ima značenje. */
function pretendPersistentRuntime(bool $booted = true): void
{
    (new ReflectionClass(Runtime::class))->setStaticPropertyValue('booted', $booted);
}

/** HTML jednog PDF predloška, bez prolaska kroz dompdf. */
function renderPdfView(Invoice $invoice, ?CompanySettings $company = null, string $view = 'pdf.invoice'): string
{
    // Predložak više ne stoji na računu, pa se za prikaz izvodi iz imena view-a.
    $pdf = app(InvoicePdfService::class);
    $template = collect(DocumentTemplate::cases())
        ->first(fn (DocumentTemplate $case): bool => $pdf->viewFor($case) === $view) ?? DocumentTemplate::Classic;

    return view($view, [
        'invoice' => $invoice->load('client', 'items', 'fiscalRecords'),
        'company' => $company ?? app(CompanySettings::class),
        'template' => $template,
        'bankAccounts' => collect(),
    ])->render();
}
