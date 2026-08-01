<?php

namespace App\Enums;

/** PDF predlošci dokumenata. */
enum DocumentTemplate: string
{
    case Classic = 'classic';
    case Modern = 'modern';
    case Minimal = 'minimal';
    case Standard = 'standard';

    public function label(): string
    {
        return match ($this) {
            self::Classic => 'Klasičan',
            self::Modern => 'Moderan',
            self::Minimal => 'Minimalan',
            self::Standard => 'Standardni',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->label()])->all();
    }
}
