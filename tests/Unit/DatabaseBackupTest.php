<?php

use App\Enums\FiscalRecordType;
use App\Models\Client;
use App\Models\FiscalTaxRate;
use App\Models\Invoice;
use App\Services\DatabaseBackup;
use App\Services\FiscalReceiptStore;
use App\Settings\CompanySettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\DatabaseFileTestCase;

/*
 * Backup radi nad stvarnim fajlom baze i pravi VACUUM, pa ovi testovi ne mogu ići
 * kroz transakciju koju Feature testovi drže. Zato zasebna datoteka baze po testu.
 */
uses(DatabaseFileTestCase::class);

beforeEach(function (): void {
    Storage::fake('local');
});

/** Račun sa jednom stavkom i sačuvanim fiskalnim dokumentom. */
function backupFixture(): Invoice
{
    FiscalTaxRate::create(['label' => 'F', 'rate' => 11, 'category_name' => 'ECAL', 'category_type' => 0]);

    $invoice = Invoice::create([
        'invoice_number' => '0001/2026',
        'client_id' => Client::create(['name' => 'Kupac iz backupa'])->id,
        'date' => now(),
        'due_date' => now()->addDays(15),
        'currency' => 'BAM',
        'language' => 'sr_Latn',
        'payment_type' => 'Cash',
        'subtotal' => 100,
        'tax_total' => 11,
        'total' => 111,
    ]);

    $record = $invoice->fiscalRecords()->create([
        'type' => FiscalRecordType::Original,
        'fiscal_invoice_number' => 'AAA-1',
        'fiscalized_at' => now(),
    ]);

    app(FiscalReceiptStore::class)->store($record, 'slika-racuna', 'png');

    return $invoice;
}

it('pravi backup sa bazom, fiskalnim dokumentima i manifestom', function (): void {
    $invoice = backupFixture();

    $backup = app(DatabaseBackup::class)->create();

    expect($backup['filename'])->toStartWith('kalkulatron-baza-')
        ->and($backup['size'])->toBeGreaterThan(0)
        ->and($backup['document_count'])->toBe(1);

    $zip = new ZipArchive;
    $zip->open($backup['path']);
    $manifest = json_decode((string) $zip->getFromName('manifest.json'), true);
    $documentName = 'fiskalni-dokumenti/'.$invoice->fiscalRecords->first()->id.'.png';

    expect($zip->getFromName('database.sqlite'))->toStartWith('SQLite format 3')
        ->and($zip->getFromName($documentName))->toBe('slika-racuna')
        ->and($manifest['invoice_count'])->toBe(1)
        ->and($manifest['app'])->toBe(config('app.name'));

    $zip->close();
});

it('vraća zatečeno stanje na ono iz backupa', function (): void {
    backupFixture();
    $backup = app(DatabaseBackup::class)->create();

    Invoice::query()->delete();
    Client::query()->delete();
    Client::create(['name' => 'Kupac nakon backupa']);

    $restored = app(DatabaseBackup::class)->restore($backup['path']);

    expect($restored['invoice_count'])->toBe(1)
        ->and($restored['document_count'])->toBe(1)
        ->and(Client::where('name', 'Kupac iz backupa')->exists())->toBeTrue()
        ->and(Client::where('name', 'Kupac nakon backupa')->exists())->toBeFalse()
        ->and(Storage::disk('local')->exists('fiscal-receipts/'.Invoice::first()->fiscalRecords->first()->id.'.png'))->toBeTrue();
});

it('odbija datoteku koja nije backup ove aplikacije', function (): void {
    $path = sys_get_temp_dir().'/nije-backup-'.uniqid().'.zip';
    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::CREATE);
    $zip->addFromString('nesto.txt', 'sadržaj');
    $zip->close();

    expect(fn () => app(DatabaseBackup::class)->restore($path))
        ->toThrow(RuntimeException::class, 'Backup ne sadrži bazu ove aplikacije.');

    File::delete($path);
});

it('reset briše podatke, podešavanja i fiskalne dokumente', function (): void {
    $invoice = backupFixture();
    $documentPath = 'fiscal-receipts/'.$invoice->fiscalRecords->first()->id.'.png';

    $company = app(CompanySettings::class);
    $company->name = 'Firma prije reseta';
    $company->save();

    app(DatabaseBackup::class)->reset();

    expect(Invoice::count())->toBe(0)
        ->and(Client::count())->toBe(0)
        ->and(FiscalTaxRate::count())->toBe(0)
        ->and(app(CompanySettings::class)->name)->not->toBe('Firma prije reseta')
        ->and(Storage::disk('local')->exists($documentPath))->toBeFalse();
});

it('sastavlja backup iz više dijelova i vraća podatke', function (): void {
    backupFixture();
    $backup = app(DatabaseBackup::class)->create();
    $contents = (string) file_get_contents($backup['path']);
    $parts = str_split($contents, (int) ceil(strlen($contents) / 3));

    Client::query()->delete();
    Invoice::query()->delete();

    foreach ($parts as $index => $part) {
        $last = $index === count($parts) - 1;
        $chunk = sys_get_temp_dir().'/dio-'.$index.'-'.uniqid();
        File::put($chunk, $part);

        $response = $this->postJson(route('settings.database.restore'), [
            'chunk' => new UploadedFile($chunk, 'backup.part', null, null, true),
            'index' => $index,
            'last' => $last,
        ])->assertSuccessful();

        $last
            ? $response->assertJson(['redirect' => route('settings.database.edit')])
            : $response->assertJson(['done' => false]);

        File::delete($chunk);
    }

    expect(Client::where('name', 'Kupac iz backupa')->exists())->toBeTrue()
        ->and(Invoice::count())->toBe(1)
        // Sastavljena arhiva se ne zadržava na uređaju.
        ->and(Storage::disk('local')->exists('backups/upload.zip'))->toBeFalse();
});

it('uz preuzimanje šalje kolačić po kojem dugme zna da je gotovo', function (): void {
    backupFixture();

    // Preuzimanje ne osvježava stranicu; bez ovog kolačića bi loader ostao da se vrti.
    $this->get(route('settings.database.download'))
        ->assertSuccessful()
        ->assertDownload()
        ->assertCookie('backup-preuzet');
});

it('reset ne vraća zatečena podešavanja u novu bazu', function (): void {
    $company = app(CompanySettings::class);
    $company->name = 'Firma prije reseta';
    $company->save();

    app(DatabaseBackup::class)->reset();

    // Podešavanja su „scoped": da nisu odbačena, prvo naredno čuvanje bi vratilo
    // staru vrijednost u svježu bazu i reset bi izgledao kao da nije uspio.
    $fresh = app(CompanySettings::class);
    $fresh->save();

    expect($fresh->name)->not->toBe('Firma prije reseta')
        ->and(app(CompanySettings::class)->name)->not->toBe('Firma prije reseta');
});
