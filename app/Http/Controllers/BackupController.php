<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendBackupRequest;
use App\Http\Requests\UpdateBackupSettingsRequest;
use App\Mail\BackupMail;
use App\Services\BackupArchive;
use App\Services\MailService;
use App\Settings\BackupSettings;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class BackupController extends Controller
{
    public function edit(BackupSettings $settings)
    {
        return view('settings.backup', [
            'settings' => $settings,
            'zipAvailable' => BackupArchive::zipAvailable(),
        ]);
    }

    public function update(UpdateBackupSettingsRequest $request, BackupSettings $settings)
    {
        $settings->email = $request->validated('email');
        $settings->save();

        return redirect()->route('settings.backup.edit')->with('status', 'Email za backup je sačuvan.');
    }

    public function send(SendBackupRequest $request, BackupSettings $settings, BackupArchive $archive, MailService $mail)
    {
        if (blank($settings->email)) {
            return redirect()->route('settings.backup.edit')->with('error', 'Prvo unesite email na koji se šalje backup.');
        }

        $backup = null;
        $deliveryFormat = $request->validated('delivery_format');
        $contents = [
            'invoices' => $request->boolean('include_invoices'),
            'fiscal_documents' => $request->boolean('include_fiscal_documents'),
            'manifest' => $request->boolean('include_manifest'),
        ];
        $startedAt = microtime(true);

        Log::channel('backup')->info('Backup started');

        try {
            $backup = $deliveryFormat === 'raw'
                ? $archive->raw($contents)
                : $archive->create($contents);
            Log::channel('backup')->info('Backup documents created', [
                'invoice_count' => $backup['invoice_count'],
                'fiscal_document_count' => $backup['fiscal_document_count'],
                'delivery_format' => $deliveryFormat,
                'elapsed_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            ]);

            [$fromAddress, $fromName] = $mail->from();
            Log::channel('backup')->info('Backup SMTP send started');

            $mail->send($settings->email, new BackupMail(
                archivePath: $backup['path'] ?? '',
                archiveName: $backup['filename'] ?? '',
                invoiceCount: $backup['invoice_count'],
                fiscalDocumentCount: $backup['fiscal_document_count'],
                deliveryFormat: $deliveryFormat,
                backupAttachments: $backup['attachments'] ?? [],
                fromAddress: $fromAddress,
                fromName: $fromName,
            ));

            $settings->last_backup_at = now();
            $settings->last_backup_invoice_count = $backup['invoice_count'];
            $settings->last_backup_fiscal_document_count = $backup['fiscal_document_count'];
            $settings->last_backup_checksum = $backup['checksum'];
            $settings->save();

            Log::channel('backup')->info('Backup sent', [
                'invoice_count' => $backup['invoice_count'],
                'fiscal_document_count' => $backup['fiscal_document_count'],
                'elapsed_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            ]);

            return redirect()->route('settings.backup.edit')
                ->with('status', "Backup je poslat: {$backup['invoice_count']} računa i {$backup['fiscal_document_count']} fiskalnih dokumenata.");
        } catch (RuntimeException $exception) {
            Log::channel('backup')->warning('Backup failed', [
                'stage' => $backup === null ? 'archive' : 'mail',
                'exception' => $exception::class,
                'elapsed_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            ]);
            report($exception);

            return redirect()->route('settings.backup.edit')->with('error', 'Slanje backupa nije uspjelo: '.$exception->getMessage());
        } catch (Throwable $exception) {
            Log::channel('backup')->warning('Backup failed', [
                'stage' => $backup === null ? 'archive' : 'mail',
                'exception' => $exception::class,
                'elapsed_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            ]);
            report($exception);

            return redirect()->route('settings.backup.edit')->with('error', 'Slanje backupa trenutno nije uspjelo. Pokušajte ponovo.');
        } finally {
            if (($backup['path'] ?? null) !== null && is_file($backup['path'])) {
                @unlink($backup['path']);
            }
        }
    }
}
