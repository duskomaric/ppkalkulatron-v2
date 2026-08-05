<?php

namespace App\Enums;

/**
 * Tok statusa:
 *
 *   prodajni račun:  Created → Fiscalized
 *   storno dokument: RefundCreated → Refunded
 *
 * Original ostaje fiskalizovan i kad mu se izda storno — kod Poreske uprave i dalje
 * postoji; poništava ga zaseban dokument, koji jedini nosi statuse storniranja.
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
