@props(['years', 'selected'])

<x-drawer title="Odabir godine" state="yearDrawer">
    <div class="space-y-2">
        @foreach ($years as $year)
            <a href="{{ request()->fullUrlWithQuery(['year' => $year, 'page' => null]) }}"
               @class([
                   'w-full flex items-center justify-center p-3 rounded-xl border transition-all',
                   'border-primary bg-primary/10 ring-1 ring-primary/20' => (int) $selected === (int) $year,
                   'border-[var(--color-border)] bg-[var(--color-surface)] hover:bg-[var(--color-surface-hover)]' => (int) $selected !== (int) $year,
               ])>
                <span @class(['text-lg font-black', 'text-primary' => (int) $selected === (int) $year])>{{ $year }}</span>
            </a>
        @endforeach
    </div>
</x-drawer>
