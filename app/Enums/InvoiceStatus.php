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

    /** Boja bedža — iste vrijednosti kao StatusBadge u v1. */
    public function badgeColor(): string
    {
        return match ($this) {
            self::Created => 'gray',
            self::Fiscalized => 'green',
            self::RefundCreated => 'amber',
            self::Refunded => 'red',
        };
    }

    public function canBeDeleted(): bool
    {
        return $this === self::Created || $this === self::RefundCreated;
    }
}
