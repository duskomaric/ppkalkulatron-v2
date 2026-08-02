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
    case OpsConsole = 'ops-console';
    case Shell = 'shell';
    case Workstation = 'workstation';
    case TerminalPaper = 'terminal-paper';
    case ProgrammerPaper = 'programmer-paper';
    case ProgrammerGrid = 'programmer-grid';
    case EditorDaylight = 'editor-daylight';
    case EditorSolarized = 'editor-solarized';
    case SignalPastel = 'signal-pastel';
    case SignalStudio = 'signal-studio';
    case OpsIce = 'ops-ice';
    case OpsGraph = 'ops-graph';

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
            self::OpsConsole => 'Ops konzola',
            self::Shell => 'Shell',
            self::Workstation => 'Radna stanica',
            self::TerminalPaper => 'Terminal papir',
            self::ProgrammerPaper => 'Programerski papir',
            self::ProgrammerGrid => 'Programerska mreža',
            self::EditorDaylight => 'Editor daylight',
            self::EditorSolarized => 'Editor solarized',
            self::SignalPastel => 'Signal pastel',
            self::SignalStudio => 'Signal studio',
            self::OpsIce => 'Ops led',
            self::OpsGraph => 'Ops graf',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->label()])->all();
    }
}
