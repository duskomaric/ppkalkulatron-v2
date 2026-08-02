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
    case Terminal = 'terminal';
    case Protocol = 'protocol';
    case Kernel = 'kernel';
    case TerminalLight = 'terminal-light';
    case Editor = 'editor';
    case Signal = 'signal';

    public function label(): string
    {
        return match ($this) {
            self::Classic => 'Klasičan',
            self::Modern => 'Moderan',
            self::Minimal => 'Minimalan',
            self::Standard => 'Standardni',
            self::Programmer => 'Programerski',
            self::Blueprint => 'Blueprint',
            self::Terminal => 'Terminal',
            self::Protocol => 'Protocol',
            self::Kernel => 'Kernel',
            self::TerminalLight => 'Svijetli terminal',
            self::Editor => 'Editor',
            self::Signal => 'Signal',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->label()])->all();
    }
}
