<?php

namespace App\Models;

use App\Enums\Unit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'unit', 'tax_label', 'gtin', 'last_unit_price', 'is_active'];

    protected $casts = [
        'unit' => Unit::class,
        'last_unit_price' => 'integer',
        'is_active' => 'boolean',
    ];
}
