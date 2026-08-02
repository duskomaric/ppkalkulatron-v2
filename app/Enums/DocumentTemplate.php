<?php

namespace App\Enums;

/** PDF predlošci dokumenata. */
enum DocumentTemplate: string
{
    case Classic = 'classic';
    case Modern = 'modern';
    case Minimal = 'minimal';
    case Standard = 'standard';
    case Programmer = 'programmer';
    case Blueprint = 'blueprint';

    public function label(): string
    {
        return match ($this) {
            self::Classic => 'Klasičan',
            self::Modern => 'Moderan',
            self::Minimal => 'Minimalan',
            self::Standard => 'Standardni',
            self::Programmer => 'Programerski',
            self::Blueprint => 'Blueprint',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->label()])->all();
    }
}
