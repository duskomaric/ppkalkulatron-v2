<?php

namespace Database\Factories;

use App\Models\FiscalTaxRate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FiscalTaxRate>
 */
class FiscalTaxRateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'label' => 'F',
            'rate' => 11,
            'category_name' => 'ECAL',
            'group_id' => 1,
            'category_type' => 0,
            'valid_from' => now(),
            'is_current' => true,
            'synced_at' => now(),
        ];
    }
}
