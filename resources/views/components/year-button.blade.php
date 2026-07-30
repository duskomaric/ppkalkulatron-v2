@props(['year'])

<button type="button" @click="yearDrawer = true"
        class="cursor-pointer h-9 px-4 rounded-full border border-primary/40 bg-primary/10 text-primary text-[10px] font-black uppercase tracking-[0.18em] flex items-center gap-2 transition-colors hover:bg-primary/20 hover:border-primary/50">
    <x-icon name="calendar" class="h-3.5 w-3.5" />
    {{ $year }}
    <x-icon name="chevron-down" class="h-3.5 w-3.5" />
</button>
