<?php

namespace App\Http\Controllers;

use App\Services\DatabaseBackup;
use App\Services\Diagnostics;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Native\Mobile\Facades\Share;
use RuntimeException;
use Throwable;

/** Puna kopija stanja aplikacije: baza i fiskalni dokumenti. */
class DatabaseBackupController extends Controller
{
    public function __construct(
        private DatabaseBackup $backup,
        private Diagnostics $diagnostics,
    ) {}

    public function edit()
    {
        return view('settings.database');
    }

    /** Na uređaju backup ide u sistemsko dijeljenje, u browseru se preuzima. */
    public function download()
    {
        try {
            $backup = $this->backup->create();
        } catch (Throwable $exception) {
            report($exception);
            $this->diagnostics->error('Backup baze nije napravljen', ['exception' => $exception::class]);

            return back()->with('error', 'Backup baze nije napravljen. Pokušajte ponovo.');
        }

        if (! isMobile()) {
            $response = response()->download($backup['path'], $backup['filename'])->deleteFileAfterSend();

            // Preuzimanje ne osvježava stranicu, pa dugme po ovom kolačiću zna da je gotovo.
            $response->headers->setCookie(cookie('backup-preuzet', '1', 1, null, null, null, false));

            return $response;
        }

        Share::file('Backup baze', $backup['filename'], $backup['path']);

        return back()->with('status', "Backup je spreman za dijeljenje: {$backup['invoice_count']} računa i {$backup['document_count']} fiskalnih dokumenata.");
    }

    /**
     * Prima backup u dijelovima i vraća podatke kad stigne posljednji.
     *
     * Uređaj rijetko prima više od par megabajta u jednom zahtjevu, a ta granica se
     * ne može podesiti iz koda — zato pregledač šalje datoteku isjeckanu, a ovdje se
     * ponovo sastavlja.
     */
    public function restore(Request $request)
    {
        if (! DatabaseBackup::restoreAvailable()) {
            return response()->json(['message' => 'Ovaj uređaj ne podržava vraćanje backupa.'], 422);
        }

        $data = $request->validate([
            'chunk' => ['required', 'file'],
            'index' => ['required', 'integer', 'min:0'],
            'last' => ['required', 'boolean'],
        ], [], ['chunk' => 'dio backupa']);

        try {
            $path = $this->backup->appendUploadChunk(
                (string) file_get_contents($data['chunk']->getRealPath()),
                (int) $data['index'],
            );

            if (! $request->boolean('last')) {
                return response()->json(['done' => false]);
            }

            $restored = $this->backup->restore($path);
        } catch (RuntimeException $exception) {
            $this->backup->discardUpload();

            return response()->json(['message' => $exception->getMessage()], 422);
        } catch (Throwable $exception) {
            report($exception);
            $this->diagnostics->error('Vraćanje baze nije uspjelo', ['exception' => $exception::class]);
            $this->backup->discardUpload();

            return response()->json(['message' => 'Backup nije vraćen. Podaci su ostali nepromijenjeni.'], 500);
        }

        $this->backup->discardUpload();

        session()->flash('status', "Baza je vraćena iz backupa: {$restored['invoice_count']} računa i {$restored['document_count']} fiskalnih dokumenata.");

        return response()->json(['redirect' => route('settings.database.edit')]);
    }

    /** Briše sve podatke i podešavanja — aplikacija kreće kao nova. */
    public function reset()
    {
        try {
            $this->backup->reset();
        } catch (Throwable $exception) {
            report($exception);
            $this->diagnostics->error('Reset aplikacije nije uspio', ['exception' => $exception::class]);

            return back()->with('error', 'Reset nije uspio. Pokušajte ponovo.');
        }

        // Sesija pripada obrisanom stanju: PIN i otključavanje kreću iznova.
        session()->flush();
        session()->regenerate();

        return redirect()->route('invoices.index')->with('status', 'Aplikacija je resetovana. Svi podaci su obrisani.');
    }

    /** Veličina posljednje pripremljene arhive, samo za prikaz u podešavanjima. */
    public static function lastArchiveSize(): ?int
    {
        $files = File::isDirectory(storage_path('app/private/backups'))
            ? File::files(storage_path('app/private/backups'))
            : [];

        return $files === [] ? null : max(array_map(fn ($file) => $file->getSize(), $files));
    }
}
