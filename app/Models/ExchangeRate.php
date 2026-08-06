<?php

namespace App\Models;

use App\Casts\DateOnly;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    use HasFactory;

    protected $fillable = ['currency', 'rate_to_bam', 'rate_date'];

    protected $casts = ['rate_to_bam' => 'decimal:8', 'rate_date' => DateOnly::class];
}
