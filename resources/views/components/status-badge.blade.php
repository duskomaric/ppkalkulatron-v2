@props(['status'])

<span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest {{ $status->badgeClasses() }}">
    {{ $status->label() }}
</span>
