@props(['icon' => 'file-text', 'title', 'action' => null, 'actionLabel' => null])

<div class="p-10 rounded-xl border-2 border-dashed border-[var(--color-text-dim)]/20 text-center bg-[var(--color-text-dim)]/5">
    <x-icon :name="$icon" class="h-8 w-8 mx-auto mb-3 text-[var(--color-text-dim)]" />
    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-[var(--color-text-dim)]">{{ $title }}</p>
    @if ($action)
        <x-button variant="primary" :href="$action" class="mt-4 !text-xs !font-black !uppercase !tracking-widest">
            <x-icon name="plus" class="h-4 w-4" />
            {{ $actionLabel }}
        </x-button>
    @endif
</div>
