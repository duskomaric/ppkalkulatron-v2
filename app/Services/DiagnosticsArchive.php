<?php

namespace App\Services;

use RuntimeException;

/** Priprema jedan tekstualni prilog od najviše sedam dana sigurnih dijagnostičkih logova. */
class DiagnosticsArchive
{
    private const MAX_LOG_BYTES = 5_000_000;

    /** @return array{path: string, filename: string, file_count: int} */
    public function create(): array
    {
        $directory = storage_path('app/private/diagnostics');

        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException('Dijagnostički izvještaj nije moguće pripremiti.');
        }

        $logs = glob(storage_path('logs/support-diagnostics-*.log')) ?: [];
        rsort($logs);
        $logs = array_slice($logs, 0, 7);
        $contents = $this->header();

        foreach ($logs as $log) {
            $contents .= "\n--- ".basename($log)." ---\n";
            $contents .= $this->safeContents($log)."\n";
        }

        $filename = 'kalkulatron-dijagnostika-'.now()->format('Y-m-d_His').'.log';
        $path = $directory.'/'.$filename;

        if (file_put_contents($path, $contents) === false) {
            throw new RuntimeException('Dijagnostički izvještaj nije moguće sačuvati.');
        }

        return ['path' => $path, 'filename' => $filename, 'file_count' => count($logs)];
    }

    private function header(): string
    {
        return implode("\n", [
            config('app.name').' — sigurni dijagnostički izvještaj',
            'Ovaj prilog ne sadrži račune, fiskalne dokumente, kupce, API ključeve, PIN, PAK ni SMTP lozinku.',
            'Verzija: '.config('nativephp.version').' (build '.config('nativephp.version_code').')',
            'Vrijeme: '.now()->toIso8601String(),
        ])."\n";
    }

    private function safeContents(string $path): string
    {
        $contents = file_get_contents($path);

        if ($contents === false) {
            return '[log nije dostupan]';
        }

        $contents = substr($contents, -self::MAX_LOG_BYTES);

        return (string) preg_replace(
            '/((?:api[_-]?key|authorization|password|secret|token|pac|pin)\s*[=:]\s*)[^,\s\]\}]+/i',
            '$1[redacted]',
            $contents,
        );
    }
}
