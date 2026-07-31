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
                    <x-toggle name="menu_modules[]" :value="$key" :hidden="false" :id="'menu-'.$key"
                              :checked="in_array($key, old('menu_modules', $settings->menu_modules), true)">
                        <span class="flex items-center gap-3">
                            <span class="h-8 w-8 bg-[var(--color-border)] rounded-lg flex items-center justify-center text-[var(--color-text-dim)] shrink-0">
                                <x-icon :name="$module['icon']" class="h-4 w-4" />
                            </span>
                            <span class="text-[13px] font-bold text-[var(--color-text-muted)]">{{ $module['title'] }}</span>
                        </span>
                    </x-toggle>
                @endforeach
            </div>
        </x-section-block>

        <x-button variant="primary" class="w-full !py-3.5 !text-[11px] !uppercase !tracking-[0.2em] !font-black">
            Sačuvaj izmjene
        </x-button>
    </form>
@endsection
