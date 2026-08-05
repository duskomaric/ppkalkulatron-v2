<?php

namespace App\Enums;

/**
 * Status prati život računa, uključujući njegov storno:
 *
 *   Created → Fiscalized → RefundCreated → Refunded
 *
 * Original i njegov storno dijele posljednja dva stanja: kad se storno kreira oba
 * su u „Storniranje", a kad se storno fiskalizuje oba su „Storniran".
 */
enum InvoiceStatus: string
{
    case Created = 'created';
    case Fiscalized = 'fiscalized';
    case RefundCreated = 'refund_created';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Created => 'Nacrt',
            self::Fiscalized => 'Fiskalizovan',
            self::RefundCreated => 'Storniranje',
            self::Refunded => 'Storniran',
        };
    }

    /** Boja statusnog bedža: siva = nacrt, zelena = važi, žuta = čeka radnju, crvena = poništeno. */
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
