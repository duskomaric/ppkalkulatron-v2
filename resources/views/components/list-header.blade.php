@props(['columns', 'grid'])

<div class="hidden md:grid {{ $grid }} gap-3 px-4 py-2 text-[10px] font-black uppercase tracking-[0.2em] text-[var(--color-text-dim)]">
    @foreach ($columns as $column)
        <span class="{{ ($column['align'] ?? 'left') === 'right' ? 'text-right' : '' }}">{{ $column['label'] }}</span>
    @endforeach
</div>
