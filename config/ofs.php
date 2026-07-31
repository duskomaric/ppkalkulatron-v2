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

];
