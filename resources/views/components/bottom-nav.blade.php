{{-- Plutajuća pilula kao u v1 --}}
<div class="lg:hidden fixed bottom-0 left-0 right-0 z-50 p-3 sm:p-4 flex justify-center pointer-events-auto safe-bottom safe-x">
    <nav class="bg-[var(--color-glass)] backdrop-blur-2xl border border-[var(--color-border-strong)] rounded-2xl sm:rounded-3xl shadow-2xl shadow-black/20 px-4 sm:px-6 py-2 sm:py-2.5 flex items-center justify-around gap-2 sm:gap-4 w-full max-w-md sm:max-w-lg">
        @foreach ($navItems as $item)
            <a href="{{ $item['href'] }}" title="{{ $item['title'] }}"
               @class([
                   'cursor-pointer group flex flex-col items-center gap-1 transition-all duration-300',
                   'scale-110 sm:scale-125 -translate-y-1' => $item['active'],
                   'hover:scale-110' => ! $item['active'],
               ])>
                <div @class([
                    'relative p-2 sm:p-2.5 rounded-2xl transition-all duration-300',
                    'bg-primary/25 shadow-glow-primary ring-1 ring-primary/40' => $item['active'],
                    'bg-[var(--color-surface)] group-hover:bg-[var(--color-surface-hover)]' => ! $item['active'],
                ])>
                    <x-icon :name="$item['icon']" @class([
                        'h-5 w-5 sm:h-6 sm:w-6',
                        'text-primary' => $item['active'],
                        'text-[var(--color-text-dim)] group-hover:text-[var(--color-text-main)]' => ! $item['active'],
                    ]) />
                </div>
                <span @class([
                    'text-[9px] sm:text-[10px] font-bold uppercase tracking-wider mt-1 sm:block max-w-[64px] sm:max-w-none truncate text-center',
                    'text-primary' => $item['active'],
                    'text-[var(--color-text-dim)] group-hover:text-[var(--color-text-main)]' => ! $item['active'],
                ])>{{ $item['title'] }}</span>
            </a>
        @endforeach
    </nav>
</div>
