<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case Created = 'created';
    case Fiscalized = 'fiscalized';
    case RefundCreated = 'refund_created';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Created => 'Kreiran',
            self::Fiscalized => 'Fiskalizovan',
            self::RefundCreated => 'Storno kreiran',
            self::Refunded => 'Storniran',
        };
    }

    /** Tailwind klase za bedž — prati boje iz v1. */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Created => 'bg-[var(--color-text-dim)]/15 text-[var(--color-text-muted)]',
            self::Fiscalized => 'bg-[var(--color-success)]/15 text-[var(--color-success)]',
            self::RefundCreated => 'bg-[var(--color-warning)]/15 text-[var(--color-warning)]',
            self::Refunded => 'bg-[var(--color-error)]/15 text-[var(--color-error)]',
        };
    }

    public function canBeDeleted(): bool
    {
        return $this === self::Created || $this === self::RefundCreated;
    }
}
