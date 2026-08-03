@props(['value'])

@php
    $selectedTemplate = old('template', $value);
    $templates = \App\Enums\DocumentTemplate::cases();
    $families = \App\Enums\DocumentTemplate::families();
    $tones = ['light' => 'Svijetli', 'dark' => 'Tamni'];
    $filterState = collect($templates)->map(fn ($template) => [$template->family(), $template->tone()])->all();
@endphp

{{--
    Galerija prikazuje cijelu A4 stranu u minijaturi, pa nema odvojenog punog pregleda.
    Filteri su Alpine-ovi jer izbor ostaje u istoj formi, bez ponovnog učitavanja.
--}}
<fieldset class="space-y-4" x-data="{
    family: 'all',
    tone: 'all',
    all: @js($filterState),
    matches(family, tone) {
        return (this.family === 'all' || this.family === family)
            && (this.tone === 'all' || this.tone === tone);
    },
    get visible() {
        return this.all.filter(([family, tone]) => this.matches(family, tone)).length;
    },
    /*
     * CSS ne može podijeliti dvije dužine, pa se uvećanje minijature računa ovdje:
     * kolona uzme svu širinu galerije i minijatura je tačno te širine, bez ostatka.
     */
    fit() {
        const grid = this.$refs.grid;
        const width = grid.clientWidth;

        if (! width) {
            return;
        }

        const gap = 12;
        const border = 4;
        const columns = Math.max(1, Math.floor((width + gap) / (300 + border + gap)));
        const track = Math.min((width - (columns - 1) * gap) / columns, 460);

        grid.style.setProperty('--tpl-scale', (track - border) / 794);
        grid.style.gridTemplateColumns = `repeat(${columns}, ${track}px)`;
    },
}" x-init="$nextTick(() => fit())" x-on:resize.window.debounce.100ms="fit()">
    <legend class="text-sm font-bold text-[var(--color-text-main)]">Predložak računa</legend>

    <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
        <p class="text-xs leading-5 text-[var(--color-text-dim)]">Izgled koji se koristi na novim računima.</p>
        <p class="text-xs font-bold text-primary">Odabrano: {{ \App\Enums\DocumentTemplate::tryFrom((string) $selectedTemplate)?->label() ?? '—' }}</p>
    </div>

    <div class="template-filters">
        <div class="template-filter-group" role="group" aria-label="Stil predloška">
            <button type="button" class="template-filter" x-on:click="family = 'all'"
                    x-bind:class="family === 'all' && 'is-active'">Svi stilovi</button>
            @foreach ($families as $familyValue => $familyLabel)
                <button type="button" class="template-filter" x-on:click="family = @js($familyValue)"
                        x-bind:class="family === @js($familyValue) && 'is-active'">{{ $familyLabel }}</button>
            @endforeach
        </div>

        <div class="template-filter-group" role="group" aria-label="Tema predloška">
            <button type="button" class="template-filter" x-on:click="tone = 'all'"
                    x-bind:class="tone === 'all' && 'is-active'">Sve teme</button>
            @foreach ($tones as $toneValue => $toneLabel)
                <button type="button" class="template-filter" x-on:click="tone = @js($toneValue)"
                        x-bind:class="tone === @js($toneValue) && 'is-active'">{{ $toneLabel }}</button>
            @endforeach
        </div>

        <p class="template-filter-count" x-text="visible + ' / {{ count($templates) }} predložaka'">{{ count($templates) }} predložaka</p>
    </div>

    <div class="template-gallery" x-ref="grid">
        @foreach ($templates as $template)
            <label class="template-gallery-label" x-show="matches(@js($template->family()), @js($template->tone()))">
                <input type="radio" name="template" value="{{ $template->value }}" class="template-gallery-input" @checked($selectedTemplate === $template->value)>
                <span class="template-gallery-card">
                    <span class="template-preview-viewport"><iframe class="template-preview-frame" src="{{ route('settings.templates.preview', $template) }}" title="Pregled: {{ $template->label() }}" loading="lazy" tabindex="-1"></iframe></span>
                    <span class="template-gallery-name">
                        <span class="template-gallery-title">{{ $template->label() }}</span>
                        <span class="template-gallery-tags">
                            <i>{{ $template->familyLabel() }}</i>
                            @if ($template->isDark())<i>{{ $tones['dark'] }}</i>@endif
                            <b aria-hidden="true">✓</b>
                        </span>
                    </span>
                </span>
            </label>
        @endforeach
    </div>

    @error('template')
        <p class="text-xs font-bold text-[var(--color-error)]">{{ $message }}</p>
    @enderror
</fieldset>
