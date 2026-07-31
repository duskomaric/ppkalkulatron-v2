@extends('layouts.app')
@section('title', 'Vizuelna podešavanja')

@section('content')
    <x-back-link :href="route('invoices.index')" />

    <form method="POST" action="{{ route('settings.menu.update') }}" class="space-y-8 animate-fade-in max-w-3xl">
        @csrf
        @method('PUT')

        <x-section-block variant="card" class="sm:p-8 space-y-6">
            <x-section-header icon="cog" title="Podešavanje menija" :help="route('help').'#meni'" />

            <p class="text-[11px] text-[var(--color-text-dim)] pl-1 leading-relaxed">
                Označeni moduli stoje u donjem meniju, ostali se otvaraju iz podešavanja.
                Ništa se ne sakriva — samo se premješta.
            </p>

            <div class="space-y-2">
                <input type="hidden" name="menu_modules[]" value="">

                @foreach ($modules as $key => $module)
                    <label class="flex items-center gap-3 border bg-[var(--color-surface)] border-[var(--color-border)] p-3 rounded-xl cursor-pointer transition-all">
                        <div class="relative flex items-center">
                            <input type="checkbox" name="menu_modules[]" value="{{ $key }}"
                                   @checked(in_array($key, old('menu_modules', $settings->menu_modules), true))
                                   class="sr-only peer">
                            <div class="w-9 h-5 bg-[var(--color-border-strong)] rounded-full peer-checked:bg-primary after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:h-4 after:w-4 after:rounded-full after:bg-gray-400 after:border after:border-gray-300 after:transition-all peer-checked:after:translate-x-full peer-checked:after:bg-white relative"></div>
                        </div>

                        <div class="h-8 w-8 bg-[var(--color-border)] rounded-lg flex items-center justify-center text-[var(--color-text-dim)] shrink-0">
                            <x-icon :name="$module['icon']" class="h-4 w-4" />
                        </div>

                        <span class="text-[13px] font-bold text-[var(--color-text-muted)]">{{ $module['title'] }}</span>
                    </label>
                @endforeach
            </div>
        </x-section-block>

        <x-button variant="primary" class="w-full !py-3.5 !text-[11px] !uppercase !tracking-[0.2em] !font-black">
            Sačuvaj izmjene
        </x-button>
    </form>
@endsection
