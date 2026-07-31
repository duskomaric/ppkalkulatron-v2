<?php

namespace App\Models;

use App\Enums\DocumentTemplate;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number', 'client_id', 'status', 'date', 'due_date', 'notes',
        'currency', 'template', 'payment_type', 'refund_invoice_id',
        'is_fiscalized', 'fiscal_invoice_number', 'fiscal_counter',
        'fiscal_verification_url', 'fiscal_request_id', 'fiscalized_at',
        'subtotal', 'tax_total', 'discount_total', 'total',
    ];

    protected $casts = [
        'status' => InvoiceStatus::class,
        'payment_type' => PaymentType::class,
        'template' => DocumentTemplate::class,
        'date' => 'date',
        'due_date' => 'date',
        'fiscalized_at' => 'datetime',
        'is_fiscalized' => 'boolean',
        'subtotal' => 'integer',
        'tax_total' => 'integer',
        'discount_total' => 'integer',
        'total' => 'integer',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /** Račun koji storniše ovaj. */
    public function refundInvoice(): BelongsTo
    {
        return $this->belongsTo(self::class, 'refund_invoice_id');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('invoice_number', 'like', "%{$term}%")
                ->orWhereHas('client', fn (Builder $c) => $c->where('name', 'like', "%{$term}%"));
        });
    }

    /** Iznosi su u pfeningu — ovo je jedino mjesto koje ih pretvara u tekst. */
    public function formatted(int $pfening): string
    {
        return number_format($pfening / 100, 2, ',', '.');
    }

    public function isDeletable(): bool
    {
        return $this->status->canBeDeleted();
    }
}
