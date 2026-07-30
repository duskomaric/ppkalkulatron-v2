<?php

namespace App\Enums;

/** Vrijednosti su one koje OFS prima — labele su naše. */
enum PaymentType: string
{
    case Cash = 'Cash';
    case Card = 'Card';
    case Check = 'Check';
    case WireTransfer = 'WireTransfer';
    case Voucher = 'Voucher';
    case MobileMoney = 'MobileMoney';
    case Other = 'Other';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Gotovina',
            self::Card => 'Kartica',
            self::Check => 'Ček',
            self::WireTransfer => 'Bankovni transfer',
            self::Voucher => 'Vaučer',
            self::MobileMoney => 'Mobilni novac',
            self::Other => 'Ostalo',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->label()])->all();
    }
}
