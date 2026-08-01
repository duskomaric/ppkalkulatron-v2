<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FiscalReceipt extends Model
{
    protected $fillable = ['fiscal_record_id', 'extension', 'path', 'checksum', 'size'];

    public function fiscalRecord(): BelongsTo
    {
        return $this->belongsTo(FiscalRecord::class);
    }
}
