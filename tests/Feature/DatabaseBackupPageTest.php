<?php

use App\Services\DatabaseBackup;
use Illuminate\Http\UploadedFile;

it('nudi backup baze, vraćanje i reset na zasebnoj stranici', function (): void {
    $html = $this->get(route('settings.database.edit'))
        ->assertSuccessful()
        ->getContent();

    expect($html)->toContain('Backup aplikacije')
        ->and($html)->toContain('Vraćanje iz backupa')
        ->and($html)->toContain('Reset aplikacije')
        ->and($html)->toContain(route('settings.database.download'))
        // Vraćanje ide kroz Alpine, pa je ruta u @js() zapisu sa escape-ovanim kosim crtama.
        ->and($html)->toContain(str_replace('/', '\/', route('settings.database.restore')))
        ->and($html)->toContain(route('settings.database.reset'))
        // Backup nosi ključ kase, PIN i lozinku maila — korisnik to mora znati.
        ->and($html)->toContain('Backup sadrži i pristupne podatke');
});

it('razdvaja arhivu na email od backupa aplikacije', function (): void {
    $archivePage = $this->get(route('settings.backup.edit'))->assertSuccessful()->getContent();
    $backupPage = $this->get(route('settings.database.edit'))->assertSuccessful()->getContent();

    // Arhiva ostaje samo mail; puna kopija i reset žive na svojoj stranici.
    expect($archivePage)->toContain('Napravi i pošalji arhivu')
        ->and($archivePage)->not->toContain(route('settings.database.reset'))
        ->and($archivePage)->toContain(route('settings.database.edit'))
        ->and($backupPage)->not->toContain(route('settings.backup.send'))
        ->and($backupPage)->toContain(route('settings.backup.edit'));
});

it('nudi obje stranice u meniju podešavanja', function (): void {
    $html = $this->get(route('invoices.index'))->assertSuccessful()->getContent();

    expect($html)->toContain('Arhiva na email')
        ->and($html)->toContain('Backup aplikacije')
        ->and($html)->toContain(route('settings.database.edit'))
        // Prazna grupa „Šifarnici" je uklonjena dok se ne popuni.
        ->and($html)->not->toContain('Šifarnici');
});

it('ne dira podatke kad odabrana datoteka nije backup', function (): void {
    $path = sys_get_temp_dir().'/tudja-arhiva-'.uniqid().'.zip';
    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::CREATE);
    $zip->addFromString('slika.png', 'sadržaj');
    $zip->close();

    $this->postJson(route('settings.database.restore'), [
        'chunk' => new UploadedFile($path, 'backup.part', 'application/zip', null, true),
        'index' => 0,
        'last' => true,
    ])->assertStatus(422)
        ->assertJson(['message' => 'Backup ne sadrži bazu ove aplikacije.']);

    @unlink($path);
});

it('traži dio backupa za vraćanje', function (): void {
    $this->postJson(route('settings.database.restore'))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['chunk', 'index', 'last']);
});

it('prekida vraćanje kad nastavak stigne bez početka', function (): void {
    $this->postJson(route('settings.database.restore'), [
        'chunk' => UploadedFile::fake()->create('backup.part', 8),
        'index' => 3,
        'last' => false,
    ])->assertStatus(422)
        ->assertJson(['message' => 'Prenos backupa je prekinut. Pokušajte ponovo.']);
});

it('šalje backup u dijelovima koje uređaj sigurno prima', function (): void {
    $chunk = DatabaseBackup::uploadChunkBytes();
    $limit = collect(['upload_max_filesize', 'post_max_size'])
        ->map(fn (string $directive): int => match (strtolower(substr((string) ini_get($directive), -1))) {
            'g' => (int) ini_get($directive) * 1024 ** 3,
            'm' => (int) ini_get($directive) * 1024 ** 2,
            'k' => (int) ini_get($directive) * 1024,
            default => (int) ini_get($directive),
        })
        ->filter()
        ->min();

    // Djelić mora proći i kroz upload_max_filesize i kroz post_max_size, uz ostatak forme.
    expect($chunk)->toBeGreaterThanOrEqual(64 * 1024)
        ->and($chunk)->toBeLessThan($limit)
        ->and($this->get(route('settings.database.edit'))->getContent())
        ->toContain('chunkBytes: '.$chunk);
});
