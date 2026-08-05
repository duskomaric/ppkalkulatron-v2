@extends('layouts.app')
@section('title', 'Izgled i navigacija')

@section('content')
    <x-back-link :href="route('invoices.index')" />

    <form method="POST" action="{{ route('settings.menu.update') }}" class="space-y-8 animate-fade-in"
          x-data="menuSettings({ modules: @js($moduleOptions), menuModules: @js($settings->menu_modules), maxMenuItems: @js($settings->max_menu_items), primaryColor: @js(old('primary_color', $settings->primary_color)) })">
        @csrf
        @method('PUT')

        <x-section-block variant="card" class="space-y-5">
            <x-section-header icon="monitor" title="Izgled aplikacije" :help="route('help').'#meni'" />

            <p class="text-[11px] leading-relaxed text-[var(--color-text-dim)]">
                Izbor teme ostaje na ovom uređaju i ne mijenja račune ni podatke kompanije.
            </p>

            <div class="flex gap-1 rounded-2xl border border-[var(--color-border)] bg-[var(--color-surface)] p-1">
                @foreach ([['light', 'Svijetla', 'sun'], ['dark', 'Tamna', 'moon'], ['system', 'Sistemska', 'monitor']] as [$value, $label, $icon])
                    <button type="button" x-on:click="$store.theme.set('{{ $value }}')"
                            class="flex min-w-0 flex-1 flex-col items-center gap-2 rounded-xl px-2 py-3 transition-colors"
                            :class="$store.theme.choice === '{{ $value }}'
                                ? 'bg-primary text-white shadow-glow-primary'
                                : 'text-[var(--color-text-dim)] hover:bg-[var(--color-surface-hover)] hover:text-[var(--color-text-main)]'">
                        <x-icon :name="$icon" class="h-5 w-5" />
                        <span class="text-[9px] font-black uppercase tracking-widest">{{ $label }}</span>
                    </button>
                @endforeach
            </div>
        </x-section-block>

        <x-section-block variant="card" class="space-y-5">
            <x-section-header icon="palette" title="Boja aplikacije" :help="route('help').'#meni'" />

            <p class="text-[11px] leading-relaxed text-[var(--color-text-dim)]">
                Boja dugmadi, oznaka i naglašenih dijelova u cijeloj aplikaciji. Odabir se vidi odmah,
                a čuva se kad sačuvate izmjene. Ikona aplikacije na telefonu ostaje onakva kakva je
                stigla sa instalacijom.
            </p>

            <div class="grid grid-cols-5 gap-2 sm:grid-cols-10">
                @foreach (\App\Support\Brand::palette() as $hex => $colourName)
                    <label class="group cursor-pointer" title="{{ $colourName }}">
                        <input type="radio" name="primary_color" value="{{ $hex }}" class="peer sr-only"
                               x-model="primaryColor" x-on:change="applyColor()"
                               @checked(old('primary_color', $settings->primary_color) === $hex)>
                        <span class="flex aspect-square w-full items-center justify-center rounded-xl border-2 border-transparent text-white transition-all peer-checked:border-[var(--color-text-main)] peer-focus-visible:ring-2 peer-focus-visible:ring-[var(--color-text-main)]"
                              style="background-color: {{ $hex }}">
                            <x-icon name="check" class="h-4 w-4 transition-opacity"
                                    x-bind:class="primaryColor === @js($hex) ? 'opacity-100' : 'opacity-0'" />
                        </span>
                        <span class="mt-1 block truncate text-center text-[9px] font-bold text-[var(--color-text-dim)]">{{ $colourName }}</span>
                    </label>
                @endforeach
            </div>
        </x-section-block>

        <x-section-block variant="card">
            <x-section-header icon="more-horizontal" title="Navigacija" :help="route('help').'#meni'" />

            <p class="text-[11px] text-[var(--color-text-dim)] pl-1 leading-relaxed">
                Izaberite redoslijed i mjesto za svaki modul. Stavke iz grupe „Više” otvaraju se
                preko zasebne ikone u navigaciji.
            </p>

            <label class="flex items-center justify-between gap-4 rounded-xl border border-[var(--color-border)] bg-[var(--color-bg)] p-4">
                <span>
                    <span class="block text-[13px] font-black">Maksimalno stavki u meniju</span>
                    <span class="block text-[10px] text-[var(--color-text-dim)]">Višak se automatski premješta u „Više”.</span>
                </span>
                <div class="relative shrink-0">
                    <x-form-select name="max_menu_items" compact placeholder="" x-model.number="maxMenuItems" @change="normalizeLimit()"
                                   class="!pl-3 !font-black">
                        @foreach (range(1, 4) as $limit)
                            <option value="{{ $limit }}">{{ $limit }}</option>
                        @endforeach
                    </x-form-select>
                </div>
            </label>

            <div class="space-y-2">
                <template x-for="module in modules" :key="module.key">
                    <div class="flex items-center gap-2 rounded-xl border border-[var(--color-border)] bg-[var(--color-bg)] p-3">
                        <span class="h-9 w-9 rounded-lg bg-primary/10 text-primary flex items-center justify-center text-xs font-black uppercase"
                              x-text="module.title.substring(0, 1)"></span>
                        <span class="min-w-0 flex-1 text-[13px] font-bold truncate" x-text="module.title"></span>
                        <div class="relative w-[145px] shrink-0">
                            <x-form-select compact placeholder="" x-model="module.placement" @change="normalizeLimit()"
                                           class="!h-10 !min-h-10 !rounded-lg !bg-[var(--color-surface)] !pl-2 !pr-8 !text-[11px] !font-black">
                                <option value="menu">Donji meni</option>
                                <option value="drawer">Više</option>
                            </x-form-select>
                        </div>
                        <div class="flex flex-col gap-0.5">
                            <button type="button" @click="move(module.key, -1)" :disabled="modules.filter((item) => item.placement === module.placement)[0]?.key === module.key"
                                    aria-label="Pomjeri modul gore"
                                    class="h-8 w-8 rounded-lg text-[var(--color-text-dim)] hover:text-primary hover:bg-primary/10 disabled:opacity-20 cursor-pointer flex items-center justify-center">
                                <x-icon name="chevron-up" class="h-4 w-4" />
                            </button>
                            <button type="button" @click="move(module.key, 1)" :disabled="modules.filter((item) => item.placement === module.placement).slice(-1)[0]?.key === module.key"
                                    aria-label="Pomjeri modul dolje"
                                    class="h-8 w-8 rounded-lg text-[var(--color-text-dim)] hover:text-primary hover:bg-primary/10 disabled:opacity-20 cursor-pointer flex items-center justify-center">
                                <x-icon name="chevron-down" class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                </template>

                <template x-for="module in menu()" :key="`menu-${module.key}`">
                    <input type="hidden" name="menu_modules[]" :value="module.key">
                </template>
                <template x-for="module in drawer()" :key="`drawer-${module.key}`">
                    <input type="hidden" name="drawer_modules[]" :value="module.key">
                </template>
            </div>
        </x-section-block>

        <x-button variant="primary" class="w-full !py-3.5 !text-[11px] !uppercase !tracking-[0.2em] !font-black">
            Sačuvaj izmjene
        </x-button>
    </form>
@endsection
