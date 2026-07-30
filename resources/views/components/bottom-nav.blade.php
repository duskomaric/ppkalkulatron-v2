{{-- Donja navigacija na telefonu — v1 je isto ima. Za sada je samo jedan modul. --}}
<nav class="lg:hidden fixed bottom-0 left-0 right-0 z-40 bg-[var(--color-glass)] backdrop-blur-lg border-t border-[var(--color-border)] pb-[env(safe-area-inset-bottom)]">
    <div class="flex items-center justify-around px-4 py-3">
        <a href="{{ route('invoices.index') }}"
           @class([
               'flex flex-col items-center gap-1 px-4 py-1 rounded-xl transition-all',
               'text-primary' => request()->routeIs('invoices.*'),
               'text-[var(--color-text-dim)]' => ! request()->routeIs('invoices.*'),
           ])>
            <x-icon name="file-text" class="h-5 w-5" />
            <span class="text-[10px] font-black uppercase tracking-widest">Računi</span>
        </a>
        <a href="{{ route('settings.pin.edit') }}"
           @class([
               'flex flex-col items-center gap-1 px-4 py-1 rounded-xl transition-all',
               'text-primary' => request()->routeIs('settings.*'),
               'text-[var(--color-text-dim)]' => ! request()->routeIs('settings.*'),
           ])>
            <x-icon name="cog" class="h-5 w-5" />
            <span class="text-[10px] font-black uppercase tracking-widest">Podešavanja</span>
        </a>
    </div>
</nav>
