<?php

namespace App\Casts;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Datum bez vremena, i pri upisu.
 *
 * Laravel-ov `date` cast skida vrijeme tek pri čitanju, pa se u kolonu upiše ono
 * što je dobio — `now()` tako ostavi „2026-08-06 13:10:46". SQLite datume poredi
 * kao tekst, pa takav zapis dođe ispred istog datuma bez vremena i poremeti
 * redoslijed liste, a ispadne i iz filtera po godini.
 *
 * @implements CastsAttributes<CarbonImmutable|null, string|null>
 */
class DateOnly implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?CarbonImmutable
    {
        return $value === null ? null : CarbonImmutable::parse($value)->startOfDay();
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $value === null ? null : CarbonImmutable::parse($value)->toDateString();
    }
}
