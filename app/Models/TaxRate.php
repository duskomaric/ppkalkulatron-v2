<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxRate extends Model
{
    use HasFactory;

    protected $fillable = ['label', 'rate', 'category_name'];

    protected $casts = ['rate' => 'integer'];

    /** Stopa u baznim poenima — 11% je 1100, kako se čuva na stavkama. */
    public function basisPoints(): int
    {
        return $this->rate * 100;
    }

    /** label => bazni poeni, za forme i obračun. */
    public static function basisPointsByLabel(): array
    {
        return static::query()->pluck('rate', 'label')->map(fn ($rate) => $rate * 100)->all();
    }
}
