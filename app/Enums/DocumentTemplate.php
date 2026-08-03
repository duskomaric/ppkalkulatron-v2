<?php

namespace App\Enums;

/** PDF predlošci dokumenata. */
enum DocumentTemplate: string
{
    public const FAMILY_BUSINESS = 'poslovni';

    public const FAMILY_EDITOR = 'editor';

    public const FAMILY_TERMINAL = 'terminal';

    public const FAMILY_SIGNAL = 'signal';

    private const FAMILIES = [
        self::FAMILY_BUSINESS => 'Poslovni',
        self::FAMILY_EDITOR => 'Kod i editor',
        self::FAMILY_TERMINAL => 'Terminal',
        self::FAMILY_SIGNAL => 'Signal i mreža',
    ];

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

    case PhpSource = 'php-source';

    case PhpDocblock = 'php-docblock';

    case PhpAttributes = 'php-attributes';

    case PhpSourceDark = 'php-source-dark';

    public function label(): string
    {
        return $this->metadata()['label'];
    }

    public function family(): string
    {
        return $this->metadata()['family'];
    }

    public function familyLabel(): string
    {
        return self::FAMILIES[$this->family()];
    }

    public function isDark(): bool
    {
        return $this->metadata()['is_dark'];
    }

    public function tone(): string
    {
        return $this->isDark() ? 'dark' : 'light';
    }

    public function view(): string
    {
        return 'pdf.invoice-'.$this->value;
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->label()])->all();
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $case): string => $case->value, self::cases());
    }

    /** @return array<string, string> */
    public static function families(): array
    {
        return self::FAMILIES;
    }

    /** @return array{label: string, family: string, is_dark: bool} */
    private function metadata(): array
    {
        return match ($this) {
            self::Classic => ['label' => 'Klasičan', 'family' => self::FAMILY_BUSINESS, 'is_dark' => false],
            self::Modern => ['label' => 'Moderan', 'family' => self::FAMILY_BUSINESS, 'is_dark' => false],
            self::Minimal => ['label' => 'Minimalan', 'family' => self::FAMILY_BUSINESS, 'is_dark' => false],
            self::Standard => ['label' => 'Standardni', 'family' => self::FAMILY_BUSINESS, 'is_dark' => false],
            self::Programmer => ['label' => 'Programerski', 'family' => self::FAMILY_EDITOR, 'is_dark' => false],
            self::Blueprint => ['label' => 'Blueprint', 'family' => self::FAMILY_EDITOR, 'is_dark' => false],
            self::Terminal => ['label' => 'Terminal', 'family' => self::FAMILY_TERMINAL, 'is_dark' => true],
            self::Protocol => ['label' => 'Protocol', 'family' => self::FAMILY_SIGNAL, 'is_dark' => false],
            self::Kernel => ['label' => 'Kernel', 'family' => self::FAMILY_TERMINAL, 'is_dark' => false],
            self::TerminalLight => ['label' => 'Svijetli terminal', 'family' => self::FAMILY_TERMINAL, 'is_dark' => false],
            self::Editor => ['label' => 'Editor', 'family' => self::FAMILY_EDITOR, 'is_dark' => true],
            self::Signal => ['label' => 'Signal', 'family' => self::FAMILY_SIGNAL, 'is_dark' => false],
            self::OpsConsole => ['label' => 'Ops konzola', 'family' => self::FAMILY_TERMINAL, 'is_dark' => true],
            self::Shell => ['label' => 'Shell', 'family' => self::FAMILY_TERMINAL, 'is_dark' => false],
            self::Workstation => ['label' => 'Radna stanica', 'family' => self::FAMILY_TERMINAL, 'is_dark' => false],
            self::TerminalMatrix => ['label' => 'Terminal matrica', 'family' => self::FAMILY_TERMINAL, 'is_dark' => false],
            self::ProgrammerCatalog => ['label' => 'Programerski katalog', 'family' => self::FAMILY_EDITOR, 'is_dark' => false],
            self::EditorMargin => ['label' => 'Editor margin', 'family' => self::FAMILY_EDITOR, 'is_dark' => false],
            self::SignalPlot => ['label' => 'Signal plot', 'family' => self::FAMILY_SIGNAL, 'is_dark' => false],
            self::OpsBoard => ['label' => 'Ops tabla', 'family' => self::FAMILY_TERMINAL, 'is_dark' => false],
            self::GitDiff => ['label' => 'Git diff', 'family' => self::FAMILY_EDITOR, 'is_dark' => false],
            self::NetworkPacket => ['label' => 'Mrežni paket', 'family' => self::FAMILY_SIGNAL, 'is_dark' => false],
            self::VsCodeDark => ['label' => 'VS Code tamni', 'family' => self::FAMILY_EDITOR, 'is_dark' => true],
            self::VsCodeLight => ['label' => 'VS Code svijetli', 'family' => self::FAMILY_EDITOR, 'is_dark' => false],
            self::PhpStormDark => ['label' => 'PhpStorm Darcula', 'family' => self::FAMILY_EDITOR, 'is_dark' => true],
            self::PhpStormLight => ['label' => 'PhpStorm svijetli', 'family' => self::FAMILY_EDITOR, 'is_dark' => false],
            self::PhpSource => ['label' => 'PHP izvorni kod', 'family' => self::FAMILY_EDITOR, 'is_dark' => false],
            self::PhpDocblock => ['label' => 'PHP docblock', 'family' => self::FAMILY_EDITOR, 'is_dark' => false],
            self::PhpAttributes => ['label' => 'PHP atributi', 'family' => self::FAMILY_EDITOR, 'is_dark' => false],
            self::PhpSourceDark => ['label' => 'PHP izvorni kod (tamni)', 'family' => self::FAMILY_EDITOR, 'is_dark' => true],
        };
    }
}
