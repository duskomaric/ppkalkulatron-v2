<?php

/**
 * Lažni ESIR za spike, kad prava kasa nije na mreži.
 *
 * Oponaša samo ono što treba da se dokaže mrežni put sa telefona na HTTP host na LAN-u:
 * plain HTTP, port 3566, iste putanje i isti headeri koje traži pravi uređaj.
 *
 *   php -S 0.0.0.0:3566 tools/mock-esir.php
 *
 * Onda sa telefona na istom Wi-Fi-u: http://<IP-racunara>:3566/api/attention
 */

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$serial = $_SERVER['HTTP_X_TERON_SERIALNUMBER'] ?? '';

header('Content-Type: application/json; charset=UTF-8');

// Vraćamo headere nazad da se u spike-u vidi da su stvarno stigli do uređaja.
$echo = [
    'mock' => true,
    'receivedAuthorization' => $auth !== '' ? substr($auth, 0, 12).'…' : null,
    'receivedSerialNumber' => $serial ?: null,
    'remoteAddr' => $_SERVER['REMOTE_ADDR'] ?? null,
];

match ($path) {
    '/api/attention' => print(json_encode($echo + ['status' => 'ok'], JSON_UNESCAPED_UNICODE)),
    '/api/status' => print(json_encode($echo + [
        'uid' => 'MOCK1234',
        'gsc' => [],  // prazno = ne traži PIN; stavi ['1500'] da testiraš PIN ekran
        'currentTaxRates' => [
            'groupId' => 3,
            'taxCategories' => [
                ['categoryId' => 0, 'name' => 'ECAL', 'taxRates' => [['rateId' => 0, 'rate' => 11, 'label' => 'F']]],
                ['categoryId' => 2, 'name' => 'PBL', 'taxRates' => [['rateId' => 2, 'rate' => 40, 'label' => 'P']]],
            ],
        ],
    ], JSON_UNESCAPED_UNICODE)),
    '/api/settings' => print(json_encode($echo + ['printerName' => 'Mock', 'lpfrUrl' => 'http://127.0.0.1:3565/api/v3'], JSON_UNESCAPED_UNICODE)),
    default => (function () use ($echo, $path) {
        http_response_code(404);
        echo json_encode($echo + ['error' => 'nepoznata putanja', 'path' => $path], JSON_UNESCAPED_UNICODE);
    })(),
};
