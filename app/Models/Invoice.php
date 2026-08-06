<?php

namespace App\Models;

use App\Casts\DateOnly;
use App\Enums\DocumentLanguage;
use App\Enums\FiscalRecordType;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number', 'client_id', 'status', 'date', 'due_date', 'notes', 'imported_at',
        'currency', 'language', 'payment_type', 'refund_invoice_id',
        'subtotal', 'tax_total', 'discount_total', 'total',
    ];

    /** Bez ovoga svježe kreiran model nema status dok se ne pročita iz baze. */
    protected $attributes = [
        'status' => InvoiceStatus::Created->value,
    ];

    protected $casts = [
        'status' => InvoiceStatus::class,
        'payment_type' => PaymentType::class,
        'language' => DocumentLanguage::class,
        'date' => DateOnly::class,
        'imported_at' => 'datetime',
        'due_date' => DateOnly::class,
        'subtotal' => 'integer',
        'tax_total' => 'integer',
        'discount_total' => 'integer',
        'total' => 'integer',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function currencyDefinition(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency', 'code');
    }

    /** Simbol se čita iz šifarnika, uz sigurnu oznaku za stare/nepoznate valute. */
    public function currencySymbol(): string
    {
        return $this->currencyDefinition?->symbol ?: $this->currency;
    }

    /** Račun čiji je ovo storno; obrnuta strana refund_invoice_id. */
    public function originalInvoice(): HasOne
    {
        return $this->hasOne(self::class, 'refund_invoice_id');
    }

    public function originalFiscalRecord(): ?FiscalRecord
    {
        return $this->fiscalRecords->firstWhere('type', FiscalRecordType::Original);
    }

    public function fiscalRecords(): HasMany
    {
        return $this->hasMany(FiscalRecord::class)->orderBy('id');
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

        return $query->where(function (Builder $q) use ($term): void {
            $q->where('invoice_number', 'like', "%{$term}%")
                ->orWhereHas('client', fn (Builder $c) => $c->where('name', 'like', "%{$term}%"));
        });
    }

    /** Iznosi su u pfeningu — ovo je jedino mjesto koje ih pretvara u tekst. */
    public function formatted(int $pfening): string
    {
        return number_format($pfening / 100, 2, ',', '.');
    }

    /**
     * Šta je jednom poslato uređaju ne smije se mijenjati ni brisati.
     *
     * Status nije dovoljna brana: originalu se pri kreiranju storna status mijenja
     * na „storno kreiran", a to je stanje u kojem je *storno* još uvijek obrisiv.
     * Postojanje fiskalnog zapisa je jednoznačno.
     */
    public function isDeletable(): bool
    {
        return $this->status->canBeDeleted() && $this->fiscalRecords()->doesntExist();
    }
}
