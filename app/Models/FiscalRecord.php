<?php

namespace App\Models;

use App\Enums\FiscalRecordType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class FiscalRecord extends Model
{
    protected $fillable = [
        'invoice_id', 'type', 'fiscal_invoice_number', 'fiscal_counter', 'request_id',
        'verification_url', 'fiscalized_at',
        // Logičko ime računa: daje mu ekstenziju i imenuje prilog u mailu.
        'fiscal_receipt_image_path',
        'fiscal_meta',
    ];

    protected $casts = [
        'type' => FiscalRecordType::class,
        'fiscalized_at' => 'datetime',
        'fiscal_meta' => 'array',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /** Ne učitavati zajedno sa listama računa — vidi migraciju slika računa. */
    public function receiptImage(): HasOne
    {
        return $this->hasOne(FiscalReceiptImage::class);
    }
}
