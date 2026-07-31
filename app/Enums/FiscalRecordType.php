<?php

namespace App\Enums;

enum FiscalRecordType: string
{
    case Original = 'original';
    case Copy = 'copy';
    case Refund = 'refund';

    public function label(): string
    {
        return match ($this) {
            self::Original => 'Original',
            self::Copy => 'Kopija',
            self::Refund => 'Refundacija',
        };
    }
}
