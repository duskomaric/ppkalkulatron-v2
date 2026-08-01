<?php

namespace App\Services;

use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Potraga za fiskalnim uređajem na lokalnoj mreži.
 *
 * PHP radi na samom uređaju: opseg se čita direktno sa mrežnog interfejsa, a
 * adrese se provjeravaju paralelno.
 */
class NetworkScanner
{
    /** Port na kojem ESIR sluša. */
    public const PORT = 3566;

    private const BATCH = 64;

    private const CONNECT_TIMEOUT = 0.3;

    /** @return string[] Adrese na kojima je uređaj odgovorio, npr. http://192.168.31.102:3566 */
    public function scan(?string $range = null, ?string $apiKey = null, ?int $port = null): array
    {
        $port ??= self::PORT;
        $addresses = $range ? $this->parseRange($range) : $this->localRange();

        if ($addresses === []) {
            return [];
        }

        $found = [];

        foreach (array_chunk($addresses, self::BATCH) as $batch) {
            $responses = Http::pool(fn (Pool $pool) => array_map(
                fn (string $ip) => $pool->as($ip)
                    ->connectTimeout(self::CONNECT_TIMEOUT)
                    ->timeout(self::CONNECT_TIMEOUT * 2)
                    ->withHeaders(array_filter(['Authorization' => $apiKey ? 'Bearer '.$apiKey : null]))
                    ->get("http://{$ip}:{$port}/api/attention"),
                $batch,
            ));

            foreach ($responses as $ip => $response) {
                // Sve što odgovori na /api/attention je ESIR; 401 znači da ključ nije dobar,
                // ali uređaj jeste tu i vrijedi ga ponuditi.
                if ($response instanceof Response
                    && in_array($response->status(), [200, 401, 403], true)) {
                    $found[] = "http://{$ip}:{$port}";
                }
            }
        }

        return $found;
    }

    /** Opseg /24 sa interfejsa uređaja; bez njega nema šta da se skenira. */
    public function localRange(): array
    {
        $ip = $this->localIp();

        if (! $ip) {
            app(Diagnostics::class)->error('Skeniranje mreže: lokalna adresa nije pronađena.');

            return [];
        }

        $prefix = implode('.', array_slice(explode('.', $ip), 0, 3));

        return array_map(fn (int $last) => "{$prefix}.{$last}", range(1, 254));
    }

    public function localIp(): ?string
    {
        // UDP „veza" ne šalje ništa — samo natjera jezgro da izabere izlazni interfejs.
        $socketIp = $this->socketLocalIp();

        if ($this->isPrivate($socketIp)) {
            return $socketIp;
        }

        foreach ($this->interfaceIps() as $candidate) {
            if ($this->isPrivate($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    protected function socketLocalIp(): ?string
    {
        $socket = @stream_socket_client('udp://8.8.8.8:53', $code, $message, 1);

        if (! $socket) {
            return null;
        }

        $name = stream_socket_get_name($socket, false);
        fclose($socket);

        return strtok((string) $name, ':') ?: null;
    }

    /** @return array<int, string> */
    protected function interfaceIps(): array
    {
        if (! function_exists('net_get_interfaces')) {
            return [];
        }

        $ips = [];

        foreach ((array) net_get_interfaces() as $interface) {
            foreach ($interface['unicast'] ?? [] as $unicast) {
                $candidate = $unicast['address'] ?? null;

                if (is_string($candidate)) {
                    $ips[] = $candidate;
                }
            }
        }

        return $ips;
    }

    /** „192.168.31.100-105" ili „192.168.31." za cijeli opseg. */
    public function parseRange(string $range): array
    {
        $range = trim($range);

        if (preg_match('/^(\d{1,3}\.\d{1,3}\.\d{1,3})\.(\d{1,3})-(\d{1,3})$/', $range, $m)) {
            [$prefix, $from, $to] = [$m[1], (int) $m[2], (int) $m[3]];

            return $this->isPrivateV4Prefix($prefix) && $from <= $to && $to <= 255
                ? array_map(fn (int $last) => "{$prefix}.{$last}", range($from, $to))
                : [];
        }

        if (preg_match('/^(\d{1,3}\.\d{1,3}\.\d{1,3})\.?$/', $range, $m)) {
            return $this->isPrivateV4Prefix($m[1])
                ? array_map(fn (int $last) => "{$m[1]}.{$last}", range(1, 254))
                : [];
        }

        return [];
    }

    private function isPrivate(?string $ip): bool
    {
        return is_string($ip)
            && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
            && ! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE);
    }

    private function isPrivateV4Prefix(string $prefix): bool
    {
        return $this->isPrivate("{$prefix}.1");
    }
}
