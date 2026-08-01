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
    ];

    protected $casts = [
        'type' => FiscalRecordType::class,
        'fiscalized_at' => 'datetime',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /** Ne učitavati zajedno sa listama računa — sadržaj je u storageu uređaja. */
    public function receipt(): HasOne
    {
        return $this->hasOne(FiscalReceipt::class);
    }
}
