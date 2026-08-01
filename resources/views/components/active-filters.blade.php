@props(['filters', 'resetUrl'])

@if (count($filters))
    <div class="flex flex-wrap items-center gap-2">
        @foreach ($filters as $filter)
            <a href="{{ $filter['clear'] }}"
               class="flex items-center gap-2 h-11 px-3 rounded-full border border-primary/20 bg-primary/10 text-[10px] font-black uppercase tracking-[0.18em] text-primary hover:bg-primary/20 transition-colors">
                <span class="text-primary/70">{{ $filter['label'] }}:</span>
                <span class="text-[var(--color-text-main)]">{{ $filter['value'] }}</span>
                <x-icon name="x" class="h-3 w-3 opacity-70" />
            </a>
        @endforeach

        <a href="{{ $resetUrl }}"
           class="h-11 px-3 rounded-full border border-red-500/20 bg-red-500/10 text-[10px] font-black uppercase tracking-[0.18em] text-red-500 hover:bg-red-500/20 transition-colors flex items-center">
            Reset
        </a>
    </div>
@endif
