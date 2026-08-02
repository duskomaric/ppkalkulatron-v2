@props(['value'])

@php
    $selectedTemplate = old('template', $value);
    $templates = \App\Enums\DocumentTemplate::cases();
    $primaryTemplates = array_slice($templates, 0, 6);
    $moreTemplates = array_slice($templates, 6);
@endphp

<fieldset class="space-y-3">
    <legend class="text-sm font-bold text-[var(--color-text-main)]">Predložak računa</legend>
    <p class="text-xs leading-5 text-[var(--color-text-dim)]">Izaberite izgled koji će se koristiti na novim računima.</p>

    <div class="template-gallery">
        @foreach ($primaryTemplates as $template)
            <label class="template-gallery-label">
                <input type="radio" name="template" value="{{ $template->value }}" class="template-gallery-input" @checked($selectedTemplate === $template->value)>
                <span class="template-gallery-card">
                    <span class="template-preview-viewport"><iframe class="template-preview-frame" src="{{ route('settings.templates.preview', ['template' => $template, 'embedded' => 1]) }}" title="Pregled: {{ $template->label() }}" loading="lazy" tabindex="-1"></iframe></span>
                    <span class="template-gallery-name">{{ $template->label() }}<b aria-hidden="true">✓</b></span>
                </span>
            </label>
            <a class="template-preview-link" href="{{ route('settings.templates.preview', $template) }}">Puni pregled</a>
        @endforeach
    </div>
    <details class="template-gallery-more">
        <summary>Prikaži još predložaka ({{ count($moreTemplates) }})</summary>
        <div class="template-gallery">
            @foreach ($moreTemplates as $template)
                <label class="template-gallery-label">
                    <input type="radio" name="template" value="{{ $template->value }}" class="template-gallery-input" @checked($selectedTemplate === $template->value)>
                    <span class="template-gallery-card">
                        <span class="template-preview-viewport"><iframe class="template-preview-frame" src="{{ route('settings.templates.preview', ['template' => $template, 'embedded' => 1]) }}" title="Pregled: {{ $template->label() }}" loading="lazy" tabindex="-1"></iframe></span>
                        <span class="template-gallery-name">{{ $template->label() }}<b aria-hidden="true">✓</b></span>
                    </span>
                </label>
                <a class="template-preview-link" href="{{ route('settings.templates.preview', $template) }}">Puni pregled</a>
            @endforeach
        </div>
    </details>
    @error('template')
        <p class="text-xs font-bold text-[var(--color-error)]">{{ $message }}</p>
    @enderror
</fieldset>
