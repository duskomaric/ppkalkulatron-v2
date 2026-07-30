@props(['icon' => 'file-text', 'title', 'action' => null, 'actionLabel' => null])

<div class="p-10 rounded-xl border-2 border-dashed border-[var(--color-text-dim)]/20 text-center bg-[var(--color-text-dim)]/5">
    <x-icon :name="$icon" class="h-8 w-8 mx-auto mb-3 text-[var(--color-text-dim)]" />
    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-[var(--color-text-dim)]">{{ $title }}</p>
    @if ($action)
        <a href="{{ $action }}" class="inline-block mt-4 text-xs font-black uppercase tracking-widest text-primary">{{ $actionLabel }}</a>
    @endif
</div>
