@props(['title'])

{{--
    Mjesto za slike ekrana, kao u v1: zasebno za telefon i za desktop.
    Slike još ne postoje — kad se naprave, dolaze u public/help/.
--}}
@php
    $slug = \Illuminate\Support\Str::slug($title);
@endphp

<div class="rounded-2xl border-2 border-dashed border-[var(--color-border)] bg-[var(--color-bg)]/40 p-4 space-y-3">
    <div class="flex items-center justify-between gap-2">
        <p class="text-[10px] font-black uppercase tracking-[0.2em] text-[var(--color-text-dim)]">
            Vizuelni prikaz: {{ $title }}
        </p>
        <span class="text-[9px] font-black uppercase tracking-widest px-2 py-0.5 rounded bg-amber-500/10 text-amber-500">
            Uskoro
        </span>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        @foreach ([['mobile', 'Mobile', 'aspect-[9/16]'], ['desktop', 'Desktop', 'aspect-[16/10]']] as [$kind, $label, $ratio])
            @php($path = "help/{$slug}-{$kind}.png")

            <div class="space-y-1.5">
                <p class="text-[9px] font-black uppercase tracking-widest text-[var(--color-text-dim)]">{{ $label }}</p>

                @if (file_exists(public_path($path)))
                    <img src="{{ asset($path) }}" alt="{{ $title }} — {{ $label }}"
                         class="w-full rounded-xl border border-[var(--color-border)]" loading="lazy">
                @else
                    <div class="{{ $ratio }} w-full rounded-xl border border-[var(--color-border)] bg-[var(--color-surface)] flex flex-col items-center justify-center gap-2 text-[var(--color-text-dim)]">
                        <x-icon name="image" class="h-6 w-6" />
                        <p class="text-[10px] font-bold text-center px-3">Slika još nije dodana</p>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>
