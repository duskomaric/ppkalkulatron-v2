<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OFS fiskalni uređaj
    |--------------------------------------------------------------------------
    |
    | U v1 su ovo bila podešavanja po kompaniji. Dok v2 nema model kompanije,
    | drže se ovdje da spike može raditi. Kad dođu kompanije, OFSService prima
    | ove vrijednosti iz njihovih podešavanja umjesto iz konfiguracije.
    |
    | Cloud:   https://pos.ofs.ba
    | Lokalni: http://192.168.x.x:3566 — plain HTTP, uređaj ne podržava TLS
    |
    */

    'base_url' => env('OFS_BASE_URL', 'https://pos.ofs.ba'),
    'api_key' => env('OFS_API_KEY'),
    'serial_number' => env('OFS_SERIAL_NUMBER'),
    'pac' => env('OFS_PAC'),


    /*
    |--------------------------------------------------------------------------
    | Poreske oznake
    |--------------------------------------------------------------------------
    |
    | Uređaj ih javlja u /api/status → currentTaxRates. Ovo je fallback za formu
    | dok ih ne budemo čitali i keširali sa uređaja. Vrijednosti su bazni poeni:
    | 1100 = 11%.
    |
    */

    'tax_labels' => [
        'F' => 1100,
        'N' => 0,
        'P' => 4000,
        'E' => 600,
        'T' => 200,
        'A' => 900,
        'B' => 0,
        'C' => 0,
    ],

];
