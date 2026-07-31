@if ($paginator->hasPages())
    {{-- Paginacija po v1: pilule sa strelicama i brojevima. --}}
    <nav class="mt-8 flex flex-wrap items-center justify-center gap-2">
        @if ($paginator->onFirstPage())
            <span class="px-3 py-2 rounded-xl bg-[var(--color-border)] text-[var(--color-text-dim)] border border-[var(--color-border)] cursor-not-allowed">
                <x-icon name="chevron-left" class="h-4 w-4" />
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Prethodna strana"
               class="px-3 py-2 rounded-xl bg-primary/10 hover:bg-primary/20 text-primary border border-primary/30 transition-all">
                <x-icon name="chevron-left" class="h-4 w-4" />
            </a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="px-3 py-2 text-sm font-bold text-[var(--color-text-dim)]">{{ $element }}</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="px-3.5 py-2 rounded-xl text-sm font-black bg-primary text-white border border-primary shadow-glow-primary">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}"
                           class="px-3.5 py-2 rounded-xl text-sm font-bold bg-[var(--color-surface)] hover:bg-[var(--color-surface-hover)] text-[var(--color-text-muted)] border border-[var(--color-border)] transition-all">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Sljedeća strana"
               class="px-3 py-2 rounded-xl bg-primary/10 hover:bg-primary/20 text-primary border border-primary/30 transition-all">
                <x-icon name="chevron-right" class="h-4 w-4" />
            </a>
        @else
            <span class="px-3 py-2 rounded-xl bg-[var(--color-border)] text-[var(--color-text-dim)] border border-[var(--color-border)] cursor-not-allowed">
                <x-icon name="chevron-right" class="h-4 w-4" />
            </span>
        @endif
    </nav>
@endif
