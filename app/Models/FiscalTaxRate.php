<?php

namespace App\Models;

use Database\Factories\FiscalTaxRateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FiscalTaxRate extends Model
{
    /** @use HasFactory<FiscalTaxRateFactory> */
    use HasFactory;

    protected $fillable = [
        'label', 'rate', 'category_name', 'category_type',
    ];

    protected $casts = [
        'rate' => 'decimal:2',
    ];

    public function basisPoints(): int
    {
        return (int) round(((float) $this->rate) * 100);
    }

    /** @return array<string, int> */
    public static function basisPointsByLabel(): array
    {
        return static::query()->get(['label', 'rate'])
            ->mapWithKeys(fn (self $rate): array => [$rate->label => $rate->basisPoints()])
            ->all();
    }
}
