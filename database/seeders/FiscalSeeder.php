<?php

namespace Database\Seeders;

use App\Models\FiscalTaxRate;
use App\Settings\FiscalSettings;
use Illuminate\Database\Seeder;

/**
 * Testni podaci fiskalne kase.
 *
 * Bez poreskih stopa se ne može dodati ni artikal ni račun, pa svježa baza mora
 * imati stope prije ostalih podataka. Ovdje stoje stope testne kase (OFS sandbox);
 * prvo preuzimanje sa stvarne kase ih zamijeni njenima.
 */
class FiscalSeeder extends Seeder
{
    /** Stope testne kase: oznaka, procenat, kategorija, tip kategorije. */
    private const TAX_RATES = [
        ['A', 9, 'VAT', 0],
        ['B', 0, 'VAT', 0],
        ['C', 0, 'VAT-EXCL', 0],
        ['E', 6, 'STT', 0],
        ['F', 11, 'ECAL', 0],
        ['N', 0, 'N-TAX', 0],
        ['P', 40, 'PBL', 2],
        ['T', 2, 'TOTL', 1],
    ];

    /** Testna kasa u OFS sandboxu; koristi se samo dok kasa nije podešena. */
    private const TEST_DEVICE = [
        'base_url' => 'https://pos.ofs.ba',
        'api_key' => 'bb7584a167578b89c459d6ab1759b0cc',
        'serial_number' => 'F41AEFFF110A4B5ABB266299A41EE479',
        'pac' => '123456',
        'device_mode' => 'cloud',
    ];

    public function run(): void
    {
        foreach (self::TAX_RATES as [$label, $rate, $category, $categoryType]) {
            FiscalTaxRate::updateOrCreate(['label' => $label], [
                'rate' => $rate,
                'category_name' => $category,
                'category_type' => $categoryType,
            ]);
        }

        $settings = app(FiscalSettings::class);

        // Podešena kasa se nikad ne prepisuje — na uređaju to može biti stvarna kasa.
        if (blank($settings->api_key)) {
            $settings->fill(self::TEST_DEVICE)->save();
        }
    }
}
