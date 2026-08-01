@props(['entityName', 'entityIcon'])

{{--
    Kartica zaglavlja, sadržaj i podnožje sa akcijama za detalj dokumenta.
--}}
<div class="flex flex-col gap-4">
    <div class="flex items-center gap-3 p-4 bg-[var(--color-border)] rounded-[24px] border border-[var(--color-border-strong)] relative overflow-hidden group">
        <div class="absolute top-0 right-0 p-4 opacity-5">
            <x-icon :name="$entityIcon" class="h-16 w-16 text-[var(--color-text-main)]" />
        </div>
        <div class="h-12 w-12 rounded-2xl flex items-center justify-center text-white font-black text-xl shadow-lg shadow-primary/20 bg-primary z-10 shrink-0">
            <x-icon :name="$entityIcon" class="h-8 w-8" />
        </div>
        <div class="z-10 min-w-0">
            <p class="font-black text-lg text-[var(--color-text-main)] tracking-tighter italic leading-tight truncate">{{ $entityName }}</p>
            <div class="flex items-center gap-2 mt-1">{{ $badges ?? '' }}</div>
        </div>
    </div>

    {{ $slot }}

    <div class="flex flex-col gap-2 pt-2">
        @isset($actions)
            <div class="flex gap-2">{{ $actions }}</div>
        @endisset
    </div>
</div>
