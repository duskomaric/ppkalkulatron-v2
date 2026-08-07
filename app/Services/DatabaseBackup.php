<?php

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use ZipArchive;
use ZipStream\ZipStream;

/**
 * Backup i vraćanje kompletne baze.
 *
 * Za razliku od mail backupa, koji nosi PDF-ove i fiskalne dokumente za arhivu,
 * ovo je puna kopija stanja aplikacije: SQLite baza i fiskalni dokumenti uz nju.
 * Iz takvog fajla se aplikacija vraća tačno onakva kakva je bila.
 */
class DatabaseBackup
{
    private const DATABASE_ENTRY = 'database.sqlite';

    private const MANIFEST_ENTRY = 'manifest.json';

    private const DOCUMENTS_PREFIX = 'fiskalni-dokumenti/';

    private const DOCUMENTS_DIRECTORY = 'fiscal-receipts';

    private const BACKUP_DIRECTORY = 'backups';

    /** Arhiva koja se sastavlja iz djelića uploada. */
    private const UPLOAD_ENTRY = 'upload.zip';

    /** SQLite fajl uvijek počinje ovim potpisom. */
    private const SQLITE_HEADER = "SQLite format 3\0";

    public function __construct(private Diagnostics $diagnostics) {}

    /** Vraćanje raspakuje arhivu, a za to treba ZIP ekstenzija — pisanje ide bez nje. */
    public static function restoreAvailable(): bool
    {
        return class_exists(ZipArchive::class);
    }

    /**
     * Veličina jednog djelića uploada.
     *
     * `upload_max_filesize` i `post_max_size` se ne mogu podesiti iz koda, a na
     * uređaju su obično par megabajta — pa se backup šalje u dijelovima koji sigurno
     * prolaze, umjesto da se odbije cijela datoteka.
     */
    public static function uploadChunkBytes(): int
    {
        $limits = array_filter([
            self::iniBytes('upload_max_filesize'),
            self::iniBytes('post_max_size'),
        ]);

        $limit = $limits === [] ? 8 * 1024 ** 2 : min($limits);

        // Ostatak forme (token, indeks, granice multiparta) mora stati uz djelić.
        return max(64 * 1024, (int) floor($limit * 0.8));
    }

    /** PHP-ov skraćeni zapis (`2M`, `512K`) u bajtove. */
    private static function iniBytes(string $directive): int
    {
        $value = trim((string) ini_get($directive));

        if ($value === '' || $value === '-1') {
            return 0;
        }

        $number = (int) $value;

        return match (strtolower(substr($value, -1))) {
            'g' => $number * 1024 ** 3,
            'm' => $number * 1024 ** 2,
            'k' => $number * 1024,
            default => $number,
        };
    }

    /**
     * @return array{path: string, filename: string, size: int, invoice_count: int, document_count: int}
     */
    public function create(): array
    {
        $directory = $this->workingDirectory();
        $filename = 'kalkulatron-baza-'.now()->format('Y-m-d_His').'.zip';
        $path = $directory.'/'.$filename;
        $snapshot = $directory.'/snapshot-'.now()->format('YmdHis').'.sqlite';

        $this->snapshotDatabase($snapshot);

        $documents = $this->documentFiles();
        $stream = @fopen($path, 'wb');

        if ($stream === false) {
            @unlink($snapshot);

            throw new RuntimeException('Nije moguće napraviti backup baze.');
        }

        try {
            $archive = new ZipStream(outputStream: $stream, sendHttpHeaders: false, outputName: null);
            $archive->addFileFromPath(self::DATABASE_ENTRY, $snapshot);

            foreach ($documents as $document) {
                $archive->addFileFromPath(self::DOCUMENTS_PREFIX.basename($document), $document);
            }

            $archive->addFile(self::MANIFEST_ENTRY, json_encode([
                'app' => config('app.name'),
                'version' => config('nativephp.version'),
                'build' => config('nativephp.version_code'),
                'created_at' => now()->toIso8601String(),
                'migration' => DB::table('migrations')->max('migration'),
                'invoice_count' => Invoice::count(),
                'document_count' => count($documents),
                'database_checksum' => hash_file('sha256', $snapshot),
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

            $archive->finish();
        } finally {
            fclose($stream);
            @unlink($snapshot);
        }

        $this->diagnostics->debug('Backup baze napravljen', [
            'bytes' => filesize($path) ?: 0,
            'document_count' => count($documents),
        ]);

        return [
            'path' => $path,
            'filename' => $filename,
            'size' => (int) (filesize($path) ?: 0),
            'invoice_count' => Invoice::count(),
            'document_count' => count($documents),
        ];
    }

    /**
     * Dodaje djelić uploada na arhivu koja se sastavlja i vraća njenu putanju.
     *
     * Djelić sa indeksom 0 kreće od nule i briše ostatke ranijeg, prekinutog pokušaja.
     */
    public function appendUploadChunk(string $contents, int $index): string
    {
        $path = $this->uploadPath();

        if ($index === 0) {
            @unlink($path);
        } elseif (! is_file($path)) {
            throw new RuntimeException('Prenos backupa je prekinut. Pokušajte ponovo.');
        }

        if (@file_put_contents($path, $contents, $index === 0 ? 0 : FILE_APPEND) === false) {
            throw new RuntimeException('Backup nije moguće sačuvati na uređaju.');
        }

        return $path;
    }

    /** Briše nedovršenu ili iskorištenu arhivu sa uploada. */
    public function discardUpload(): void
    {
        @unlink($this->uploadPath());
    }

    /**
     * Vraća bazu iz arhive. Prethodno stanje se čuva, pa se pogrešno odabran fajl
     * može poništiti kopijom iz `backups` direktorija.
     *
     * @return array{invoice_count: int, document_count: int, created_at: ?string}
     */
    public function restore(string $archivePath): array
    {
        if (! self::restoreAvailable()) {
            throw new RuntimeException('Ovaj uređaj ne podržava raspakivanje backupa.');
        }

        $zip = new ZipArchive;

        if ($zip->open($archivePath) !== true) {
            throw new RuntimeException('Datoteka nije ispravan backup.');
        }

        try {
            $database = $zip->getFromName(self::DATABASE_ENTRY);

            if (! is_string($database) || ! str_starts_with($database, self::SQLITE_HEADER)) {
                throw new RuntimeException('Backup ne sadrži bazu ove aplikacije.');
            }

            $manifest = json_decode((string) $zip->getFromName(self::MANIFEST_ENTRY), true);
            $documents = $this->documentEntries($zip);

            $this->replaceDatabase($database);
            $this->replaceDocuments($zip, $documents);
        } finally {
            $zip->close();
        }

        // Backup može biti iz starije verzije aplikacije; migracije dopune šemu.
        Artisan::call('migrate', ['--force' => true]);
        Artisan::call('cache:clear');

        $this->diagnostics->debug('Baza vraćena iz backupa', [
            'invoice_count' => Invoice::count(),
            'document_count' => count($documents),
        ]);

        return [
            'invoice_count' => Invoice::count(),
            'document_count' => count($documents),
            'created_at' => is_array($manifest) ? ($manifest['created_at'] ?? null) : null,
        ];
    }

    /** Briše sve i vraća aplikaciju u stanje svježe instalacije. */
    public function reset(): void
    {
        Artisan::call('migrate:fresh', ['--force' => true]);

        Storage::disk('local')->deleteDirectory(self::DOCUMENTS_DIRECTORY);
        File::cleanDirectory($this->workingDirectory());

        /*
         * Podešavanja su „scoped" objekti: poslije brisanja baze u memoriji ostaju
         * stare vrijednosti, pa bi ih prvo naredno čuvanje upisalo nazad. Zato se
         * odbacuju odmah — inače reset zna izgledati kao da nije sve obrisao.
         */
        app()->forgetScopedInstances();

        Artisan::call('cache:clear');

        $this->diagnostics->error('Aplikacija je resetovana', ['invoice_count' => Invoice::count()]);
    }

    /**
     * `VACUUM INTO` pravi konzistentnu kopiju i dok aplikacija radi; obična kopija
     * fajla može uhvatiti bazu usred upisa.
     */
    private function snapshotDatabase(string $target): void
    {
        @unlink($target);

        try {
            DB::statement('VACUUM INTO ?', [$target]);
        } catch (\Throwable $exception) {
            $source = (string) config('database.connections.sqlite.database');

            if (! is_file($source) || ! @copy($source, $target)) {
                throw new RuntimeException('Nije moguće pročitati bazu za backup.');
            }
        }
    }

    private function replaceDatabase(string $contents): void
    {
        $target = (string) config('database.connections.sqlite.database');

        if ($target === '' || ! is_file($target)) {
            throw new RuntimeException('Baza aplikacije nije pronađena.');
        }

        $safetyCopy = $this->workingDirectory().'/prije-vracanja-'.now()->format('Y-m-d_His').'.sqlite';
        @copy($target, $safetyCopy);

        DB::disconnect();

        if (@file_put_contents($target, $contents) === false) {
            @copy($safetyCopy, $target);

            throw new RuntimeException('Bazu nije moguće zamijeniti podacima iz backupa.');
        }

        // SQLite piše i u -wal/-shm; ostaci bi se pomiješali sa vraćenom bazom.
        foreach (['-wal', '-shm'] as $suffix) {
            @unlink($target.$suffix);
        }

        DB::reconnect();
    }

    /** @param array<int, string> $entries */
    private function replaceDocuments(ZipArchive $zip, array $entries): void
    {
        $disk = Storage::disk('local');
        $disk->deleteDirectory(self::DOCUMENTS_DIRECTORY);

        foreach ($entries as $entry) {
            $contents = $zip->getFromName($entry);

            if (is_string($contents)) {
                $disk->put(self::DOCUMENTS_DIRECTORY.'/'.basename($entry), $contents);
            }
        }
    }

    /** @return array<int, string> */
    private function documentEntries(ZipArchive $zip): array
    {
        $entries = [];

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = (string) $zip->getNameIndex($index);

            if (str_starts_with($name, self::DOCUMENTS_PREFIX) && ! str_ends_with($name, '/')) {
                $entries[] = $name;
            }
        }

        return $entries;
    }

    /** @return array<int, string> apsolutne putanje fiskalnih dokumenata */
    private function documentFiles(): array
    {
        $disk = Storage::disk('local');

        return array_map(
            fn (string $path): string => $disk->path($path),
            $disk->files(self::DOCUMENTS_DIRECTORY),
        );
    }

    private function uploadPath(): string
    {
        return $this->workingDirectory().'/'.self::UPLOAD_ENTRY;
    }

    private function workingDirectory(): string
    {
        $directory = Storage::disk('local')->path(self::BACKUP_DIRECTORY);

        File::ensureDirectoryExists($directory);

        return $directory;
    }
}
