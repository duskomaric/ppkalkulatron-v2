@props(['title', 'icon' => null])

<section class="p-5 rounded-2xl border border-[var(--color-border)] bg-[var(--color-surface)]/60 space-y-4">
    <div class="flex items-center gap-2">
        @if ($icon)
            <span class="h-7 w-7 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0">
                <x-icon :name="$icon" class="h-3.5 w-3.5" />
            </span>
        @endif
        <h2 class="text-[11px] font-black uppercase tracking-[0.15em] text-[var(--color-text-muted)]">{{ $title }}</h2>
    </div>

    {{ $slot }}
</section>
