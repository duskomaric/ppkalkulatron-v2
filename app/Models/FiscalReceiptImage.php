<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FiscalReceiptImage extends Model
{
    protected $fillable = ['fiscal_record_id', 'extension', 'contents'];

    /** Base64 sadržaj nikad ne izlazi slučajno kroz serijalizaciju. */
    protected $hidden = ['contents'];

    public function fiscalRecord(): BelongsTo
    {
        return $this->belongsTo(FiscalRecord::class);
    }

    public function binary(): string
    {
        return base64_decode($this->contents, true) ?: '';
    }
}
