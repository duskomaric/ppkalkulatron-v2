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

    /**
     * Rokovi za pregled porta: prvi kratak, drugi samo ako se ništa nije javilo.
     *
     * Kad se adresa prvi put dodirne, uređaj tek treba da odgovori na ARP upit, a na
     * Wi-Fi-ju radio zna biti u štednji — prvih nekoliko stotina milisekundi zna se
     * izgubiti. Prvi pregled usput „budi" mrežu, pa drugi rijetko i zatreba.
     *
     * @var list<float> sekunde
     */
    private const PORT_DEADLINES = [1.5, 2.5];

    /**
     * Rezervni put preko HTTP-a, ako uređaj ne dozvoljava otvaranje sirovih konekcija.
     *
     * @var list<array{connect: float, timeout: float}>
     */
    private const PASSES = [
        ['connect' => 0.5, 'timeout' => 1.0],
        ['connect' => 2.0, 'timeout' => 3.0],
    ];

    public function __construct(private Diagnostics $diagnostics) {}

    /** @return string[] Adrese na kojima je uređaj odgovorio, npr. http://192.168.31.102:3566 */
    public function scan(?string $range = null, ?string $apiKey = null, ?int $port = null): array
    {
        $port ??= self::PORT;
        $addresses = $range ? $this->parseRange($range) : $this->localRange();

        if ($addresses === []) {
            return [];
        }

        $started = microtime(true);
        $socketsUsable = true;

        // Cijela podmreža se prvo pregleda odjednom, samim otvaranjem konekcija: to je
        // jedan rok za sve adrese umjesto niza HTTP zahtjeva po grupama.
        foreach (self::PORT_DEADLINES as $index => $deadline) {
            $candidates = $this->openPorts($addresses, $port, $deadline);

            if ($candidates === null) {
                $socketsUsable = false;

                break;
            }

            $found = $candidates === [] ? [] : $this->verify($candidates, $port, $apiKey);

            if ($found !== []) {
                $this->diagnostics->debug('Skeniranje mreže: uređaj pronađen', [
                    'pass' => $index + 1,
                    'addresses' => count($addresses),
                    'open_ports' => count($candidates),
                    'found' => count($found),
                    'seconds' => round(microtime(true) - $started, 2),
                ]);

                return $found;
            }
        }

        foreach ($socketsUsable ? [] : self::PASSES as $index => $pass) {
            $found = $this->sweep($addresses, $port, $apiKey, $pass['connect'], $pass['timeout']);

            if ($found !== []) {
                $this->diagnostics->debug('Skeniranje mreže: uređaj pronađen preko HTTP-a', [
                    'pass' => $index + 1,
                    'addresses' => count($addresses),
                    'found' => count($found),
                    'seconds' => round(microtime(true) - $started, 2),
                ]);

                return $found;
            }
        }

        $this->diagnostics->error('Skeniranje mreže: nijedan uređaj nije odgovorio', [
            'addresses' => count($addresses),
            'port' => $port,
            'seconds' => round(microtime(true) - $started, 2),
        ]);

        return [];
    }

    /**
     * Adrese na kojima je port otvoren.
     *
     * Konekcije se otvaraju sve odjednom i bez čekanja, pa se jednim `stream_select`
     * čeka koja se javi — cijela podmreža stane u jedan rok. Provjera je samo „ima li
     * nekoga na portu", a je li to zaista kasa utvrđuje HTTP provjera nad kandidatima.
     *
     * @param  list<string>  $addresses
     * @return list<string>|null `null` kad uređaj ne dozvoljava otvaranje konekcija
     */
    protected function openPorts(array $addresses, int $port, float $deadline): ?array
    {
        $open = [];

        foreach (array_chunk($addresses, $this->socketBudget()) as $group) {
            $sockets = [];

            foreach ($group as $ip) {
                $socket = @stream_socket_client(
                    "tcp://{$ip}:{$port}", $code, $message, 0,
                    STREAM_CLIENT_ASYNC_CONNECT | STREAM_CLIENT_CONNECT,
                );

                if ($socket !== false) {
                    $sockets[$ip] = $socket;
                }
            }

            if ($sockets === []) {
                return null;
            }

            $stop = microtime(true) + $deadline;

            while ($sockets !== [] && microtime(true) < $stop) {
                $write = array_values($sockets);
                $read = $except = [];
                $left = max(0.0, $stop - microtime(true));

                if (@stream_select($read, $write, $except, (int) $left, (int) (fmod($left, 1) * 1_000_000)) === false) {
                    break;
                }

                foreach ($write as $socket) {
                    $ip = (string) array_search($socket, $sockets, true);
                    unset($sockets[$ip]);

                    // Spremno za pisanje znači da je konekcija završila; ime udaljene
                    // strane postoji samo ako je i uspjela.
                    if (@stream_socket_get_name($socket, true) !== false) {
                        $open[] = $ip;
                    }

                    fclose($socket);
                }
            }

            foreach ($sockets as $socket) {
                fclose($socket);
            }
        }

        return $open;
    }

    /**
     * Na portu može biti bilo šta; ESIR se prepoznaje po odgovoru na `/api/attention`.
     *
     * @param  list<string>  $candidates
     * @return string[]
     */
    private function verify(array $candidates, int $port, ?string $apiKey): array
    {
        return $this->sweep($candidates, $port, $apiKey, 2.0, 3.0);
    }

    /** Koliko konekcija smije biti otvoreno odjednom, prema ograničenju uređaja. */
    private function socketBudget(): int
    {
        $limit = function_exists('posix_getrlimit')
            ? (int) (posix_getrlimit()['soft openfiles'] ?? 256)
            : 256;

        return max(64, min(254, $limit - 64));
    }

    /**
     * Jedan prolaz kroz sve adrese, po grupama koje idu uporedo.
     *
     * @param  list<string>  $addresses
     * @return string[]
     */
    private function sweep(array $addresses, int $port, ?string $apiKey, float $connect, float $timeout): array
    {
        $found = [];

        foreach (array_chunk($addresses, self::BATCH) as $batch) {
            $responses = Http::pool(fn (Pool $pool) => array_map(
                fn (string $ip) => $pool->as($ip)
                    ->connectTimeout($connect)
                    ->timeout($timeout)
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
            $this->diagnostics->error('Skeniranje mreže: lokalna adresa nije pronađena.');

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
