<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateDiagnosticsSettingsRequest;
use App\Mail\DiagnosticsMail;
use App\Services\Diagnostics;
use App\Services\DiagnosticsArchive;
use App\Services\MailService;
use App\Settings\DiagnosticsSettings;
use Illuminate\Http\RedirectResponse;
use RuntimeException;
use Throwable;

class DiagnosticsController extends Controller
{
    public function edit(DiagnosticsSettings $settings)
    {
        return view('settings.diagnostics', compact('settings'));
    }

    public function update(UpdateDiagnosticsSettingsRequest $request, DiagnosticsSettings $settings): RedirectResponse
    {
        $settings->email = $request->validated('email');
        $settings->detailed_until = $request->boolean('detailed_logging') ? now()->addDay() : null;
        $settings->save();

        return redirect()->route('settings.diagnostics.edit')->with(
            'status',
            $settings->detailedLoggingEnabled()
                ? 'Detaljna dijagnostika je uključena naredna 24 sata.'
                : 'Detaljna dijagnostika je isključena. Greške se i dalje sigurno bilježe.',
        );
    }

    public function send(DiagnosticsSettings $settings, DiagnosticsArchive $archive, MailService $mail, Diagnostics $diagnostics): RedirectResponse
    {
        if (blank($settings->email)) {
            return redirect()->route('settings.diagnostics.edit')->with('error', 'Prvo unesite email za dijagnostiku.');
        }

        $report = null;

        try {
            $diagnostics->debug('Diagnostics report requested');
            $report = $archive->create();
            [$fromAddress, $fromName] = $mail->from();
            $mail->send($settings->email, new DiagnosticsMail(
                reportPath: $report['path'],
                reportName: $report['filename'],
                fromAddress: $fromAddress,
                fromName: $fromName,
            ));

            $settings->last_sent_at = now();
            $settings->save();
            $diagnostics->debug('Diagnostics report sent', ['log_files' => $report['file_count']]);

            return redirect()->route('settings.diagnostics.edit')->with('status', 'Sigurni dijagnostički izvještaj je poslat.');
        } catch (RuntimeException $exception) {
            $diagnostics->error('Diagnostics report failed', ['exception' => $exception::class]);

            return redirect()->route('settings.diagnostics.edit')->with('error', 'Slanje dijagnostike nije uspjelo: '.$exception->getMessage());
        } catch (Throwable $exception) {
            $diagnostics->error('Diagnostics report failed', ['exception' => $exception::class]);
            report($exception);

            return redirect()->route('settings.diagnostics.edit')->with('error', 'Slanje dijagnostike trenutno nije uspjelo. Pokušajte ponovo.');
        } finally {
            if (($report['path'] ?? null) && is_file($report['path'])) {
                @unlink($report['path']);
            }
        }
    }
}
