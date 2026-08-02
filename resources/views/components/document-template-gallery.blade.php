@props(['value'])

@php
    $selectedTemplate = old('template', $value);
    $templates = \App\Enums\DocumentTemplate::cases();
@endphp

<fieldset class="space-y-3">
    <legend class="text-sm font-bold text-[var(--color-text-main)]">Predložak računa</legend>
    <p class="text-xs leading-5 text-[var(--color-text-dim)]">Izaberite izgled koji će se koristiti na novim računima.</p>

    <div class="template-gallery">
        @foreach ($templates as $template)
            @php
                $previewTheme = match ($template) {
                    \App\Enums\DocumentTemplate::Terminal, \App\Enums\DocumentTemplate::OpsConsole => 'dark-cyan',
                    \App\Enums\DocumentTemplate::Programmer, \App\Enums\DocumentTemplate::Editor => 'dark-violet',
                    \App\Enums\DocumentTemplate::Signal => 'dark-pink',
                    \App\Enums\DocumentTemplate::Standard, \App\Enums\DocumentTemplate::Kernel, \App\Enums\DocumentTemplate::Shell => 'light-amber',
                    \App\Enums\DocumentTemplate::Modern, \App\Enums\DocumentTemplate::Protocol, \App\Enums\DocumentTemplate::Workstation => 'light-blue',
                    default => 'light-teal',
                };
            @endphp
            <label class="template-gallery-label">
                <input type="radio" name="template" value="{{ $template->value }}" class="template-gallery-input" @checked($selectedTemplate === $template->value)>
                <span class="template-gallery-card">
                    <span class="template-preview template-preview--{{ $previewTheme }}">
                        <span class="template-preview-dots"><i></i><i></i><i></i></span>
                        <span class="template-preview-title"></span>
                        <span class="template-preview-subtitle"></span>
                        <span class="template-preview-table"><i></i><i></i><i></i><i></i></span>
                        <span class="template-preview-total"></span>
                    </span>
                    <span class="template-gallery-name">{{ $template->label() }}<b aria-hidden="true">✓</b></span>
                </span>
            </label>
        @endforeach
    </div>
    @error('template')
        <p class="text-xs font-bold text-[var(--color-error)]">{{ $message }}</p>
    @enderror
</fieldset>
