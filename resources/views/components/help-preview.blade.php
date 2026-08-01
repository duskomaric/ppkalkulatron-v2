@props(['title' => 'Aplikacija', 'name' => 'app'])

@php
    $mobilePath = "help/{$name}-mobile.jpg";
    $desktopPath = "help/{$name}-desktop.jpg";
@endphp

@if (file_exists(public_path($mobilePath)) && file_exists(public_path($desktopPath)))
    <div class="space-y-3 rounded-2xl border border-[var(--color-border)] bg-[var(--color-bg)]/40 p-4">
        <p class="text-[10px] font-black uppercase tracking-[0.2em] text-[var(--color-text-dim)]">
            Prikaz: {{ $title }}
        </p>

        <div class="grid grid-cols-1 gap-3 md:grid-cols-5 md:items-start">
            <figure class="space-y-1.5 md:col-span-2">
                <figcaption class="text-[9px] font-black uppercase tracking-widest text-[var(--color-text-dim)]">Telefon</figcaption>
                <img src="{{ asset($mobilePath) }}" alt="{{ $title }} na telefonu"
                     class="w-full rounded-xl border border-[var(--color-border)] shadow-lg" loading="lazy">
            </figure>

            <figure class="space-y-1.5 md:col-span-3">
                <figcaption class="text-[9px] font-black uppercase tracking-widest text-[var(--color-text-dim)]">Desktop</figcaption>
                <img src="{{ asset($desktopPath) }}" alt="{{ $title }} na desktopu"
                     class="w-full rounded-xl border border-[var(--color-border)] shadow-lg" loading="lazy">
            </figure>
        </div>
    </div>
@endif
