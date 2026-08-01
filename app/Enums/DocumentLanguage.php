<?php

namespace App\Enums;

/**
 * Jezici dostupni na dokumentima.
 *
 * Za sada se samo čuva i prikazuje; PDF-ovi izlaze na jednom jeziku dok se
 * prevodi ne implementiraju.
 */
enum DocumentLanguage: string
{
    case English = 'en';
    case Bosnian = 'bs';
    case Croatian = 'hr';
    case SerbianLatin = 'sr_Latn';
    case SerbianCyrillic = 'sr_Cyrl';
    case French = 'fr';
    case German = 'de';
    case Italian = 'it';
    case Russian = 'ru';

    public function label(): string
    {
        return match ($this) {
            self::English => 'English',
            self::Bosnian => 'Bosanski',
            self::Croatian => 'Hrvatski',
            self::SerbianLatin => 'Srpski',
            self::SerbianCyrillic => 'Српски',
            self::French => 'French',
            self::German => 'German',
            self::Italian => 'Italian',
            self::Russian => 'Russian',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->label()])->all();
    }
}
