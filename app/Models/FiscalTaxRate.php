<?php

namespace App\Models;

use Database\Factories\FiscalTaxRateFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FiscalTaxRate extends Model
{
    /** @use HasFactory<FiscalTaxRateFactory> */
    use HasFactory;

    protected $fillable = [
        'label', 'rate', 'category_name', 'group_id', 'category_type', 'valid_from', 'is_current', 'synced_at',
    ];

    protected $casts = [
        'rate' => 'decimal:2',
        'valid_from' => 'datetime',
        'is_current' => 'boolean',
        'synced_at' => 'datetime',
    ];

    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where('is_current', true);
    }

    public function basisPoints(): int
    {
        return (int) round(((float) $this->rate) * 100);
    }

    /** @return array<string, int> */
    public static function currentBasisPointsByLabel(): array
    {
        return static::query()->current()->get(['label', 'rate'])
            ->mapWithKeys(fn (self $rate): array => [$rate->label => $rate->basisPoints()])
            ->all();
    }
}
