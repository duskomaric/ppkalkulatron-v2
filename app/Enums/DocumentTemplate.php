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
    case TerminalMatrix = 'terminal-matrix';
    case ProgrammerCatalog = 'programmer-catalog';
    case EditorMargin = 'editor-margin';
    case SignalPlot = 'signal-plot';
    case OpsBoard = 'ops-board';
    case GitDiff = 'git-diff';
    case NetworkPacket = 'network-packet';
    case VsCodeDark = 'vscode-dark';
    case VsCodeLight = 'vscode-light';
    case PhpStormDark = 'phpstorm-dark';
    case PhpStormLight = 'phpstorm-light';

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
            self::TerminalMatrix => 'Terminal matrica',
            self::ProgrammerCatalog => 'Programerski katalog',
            self::EditorMargin => 'Editor margin',
            self::SignalPlot => 'Signal plot',
            self::OpsBoard => 'Ops tabla',
            self::GitDiff => 'Git diff',
            self::NetworkPacket => 'Mrežni paket',
            self::VsCodeDark => 'VS Code tamni',
            self::VsCodeLight => 'VS Code svijetli',
            self::PhpStormDark => 'PhpStorm Darcula',
            self::PhpStormLight => 'PhpStorm svijetli',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->label()])->all();
    }
}
