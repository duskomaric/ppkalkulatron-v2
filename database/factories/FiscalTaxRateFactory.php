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
            'category_type' => 0,
        ];
    }
}
