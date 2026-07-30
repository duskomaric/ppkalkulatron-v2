<?php

namespace App\Enums;

enum Unit: string
{
    case Kom = 'kom';
    case Sat = 'sat';
    case Kg = 'kg';
    case G = 'g';
    case L = 'l';
    case M = 'm';
    case M2 = 'm2';
    case M3 = 'm3';
    case Pak = 'pak';
    case Kut = 'kut';
    case Par = 'par';
    case Usl = 'usl';

    public function label(): string
    {
        return match ($this) {
            self::M2 => 'm²',
            self::M3 => 'm³',
            default => $this->value,
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->label()])->all();
    }
}
