@props(['icon' => null, 'title', 'subtitle' => null, 'help' => null])

<div class="flex items-center justify-between gap-2">
    <div class="flex items-center gap-2">
        @if ($icon)
            <div class="h-7 w-7 bg-primary/10 text-primary rounded-lg flex items-center justify-center shrink-0">
                <x-icon :name="$icon" class="h-4 w-4" />
            </div>
        @endif
        <div>
            <p class="text-[10px] font-black uppercase tracking-[0.2em] text-[var(--color-text-dim)]">{{ $title }}</p>
            @if ($subtitle)<p class="text-[10px] text-[var(--color-text-dim)]">{{ $subtitle }}</p>@endif
        </div>
    </div>

    @if ($help)
        <a href="{{ $help }}" title="Pomoć"
           class="shrink-0 h-8 w-8 rounded-lg border border-[var(--color-border)] flex items-center justify-center text-[var(--color-text-dim)] hover:text-primary hover:border-primary/30 transition-all">
            <x-icon name="info" class="h-4 w-4" />
        </a>
    @endif
</div>
