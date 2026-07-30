<?php

namespace App\Models;

use App\Enums\Unit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'article_id', 'name', 'description', 'unit', 'tax_label',
        'quantity', 'unit_price', 'tax_rate', 'subtotal', 'tax_amount', 'total',
    ];

    protected $casts = [
        'unit' => Unit::class,
        'quantity' => 'integer',
        'unit_price' => 'integer',
        'tax_rate' => 'integer',
        'subtotal' => 'integer',
        'tax_amount' => 'integer',
        'total' => 'integer',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }
}
