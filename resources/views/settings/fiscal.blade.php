@extends('layouts.app')
@section('title', 'Fiskalizacija')

@section('content')
    <x-back-link :href="route('invoices.index')" />

    <form method="POST" action="{{ route('settings.fiscal.update') }}" class="space-y-8 animate-fade-in"
          x-data="{ layout: '{{ old('receipt_layout', $settings->receipt_layout) }}' }">
        @csrf
        @method('PUT')

        <x-section-block variant="card" class="sm:p-8 space-y-6">
            <x-section-header icon="file-text" title="Uređaj" :help="route('help').'#fiskalizacija'" />

            <x-form-select label="Način uređaja" name="device_mode" :value="$settings->device_mode" required
                           :options="['cloud' => 'Cloud (pos.ofs.ba)', 'local' => 'Lokalni ESIR na mreži']" />

            <x-form-input label="Base URL" name="base_url" :value="$settings->base_url" required icon="cog"
                          hint="Cloud: https://pos.ofs.ba — lokalni: http://192.168.x.x:3566" />

            <x-form-input label="API ključ" name="api_key" :value="$settings->api_key" icon="lock" />

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-form-input label="Serijski broj" name="serial_number" :value="$settings->serial_number"
                              hint="Samo za cloud." />
                <x-form-input label="PAK" name="pac" :value="$settings->pac" hint="Samo za cloud." />
            </div>
        </x-section-block>

        <x-section-block variant="card" class="sm:p-8 space-y-6">
            <x-section-header icon="file-text" title="Štampa računa" />

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-form-select label="Izgled računa" name="receipt_layout" :value="$settings->receipt_layout" required
                               x-model="layout" :options="['Slip' => 'Slip (termalni)', 'Invoice' => 'Invoice (A4)']" />

                <div class="space-y-1.5 w-full">
                    <x-field-label variant="settings" required for="receipt_image_format">Format slike</x-field-label>
                    <div class="relative">
                        <select id="receipt_image_format" name="receipt_image_format" required
                                class="w-full h-12 px-4 bg-[var(--color-bg)] border border-[var(--color-border)] rounded-xl focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all font-bold text-sm appearance-none cursor-pointer">
                            {{-- A4 nema PNG: uređaj vrati praznu jednopikselnu sliku. --}}
                            <option value="Png" x-show="layout !== 'Invoice'" @selected(old('receipt_image_format', $settings->receipt_image_format) === 'Png')>Png</option>
                            <option value="Pdf" @selected(old('receipt_image_format', $settings->receipt_image_format) === 'Pdf')>Pdf</option>
                            <option value="Html" @selected(old('receipt_image_format', $settings->receipt_image_format) === 'Html')>Html</option>
                        </select>
                        <div class="absolute right-4 top-1/2 -translate-y-1/2 text-[var(--color-text-dim)] pointer-events-none">
                            <x-icon name="chevron-down" class="h-4 w-4" />
                        </div>
                    </div>
                    @error('receipt_image_format')<p class="text-[10px] font-bold text-red-500 ml-1 uppercase tracking-tight">{{ $message }}</p>@enderror
                </div>
            </div>

            <x-form-select label="Podrazumijevani način plaćanja" name="default_payment_type"
                           :value="$settings->default_payment_type" required :options="\App\Enums\PaymentType::options()" />

            <x-form-textarea label="Linije u zaglavlju računa" name="receipt_header_text_lines" rows="3"
                             :value="implode(\"\n\", $settings->receipt_header_text_lines)" />

            <x-toggle name="render_receipt_image" :checked="$settings->render_receipt_image" label="Generiši sliku računa" />
            <x-toggle name="print_receipt" :checked="$settings->print_receipt" label="Štampaj račun pri fiskalizaciji" />
        </x-section-block>

        <x-section-block variant="card" class="sm:p-8 space-y-6">
            <x-section-header icon="hash" title="Veleprodaja" />

            <x-toggle name="wholesale" :checked="$settings->wholesale" label="Veleprodaja (VP)" />

            <p class="text-[11px] text-[var(--color-text-dim)] pl-1 leading-relaxed">
                Promet se evidentira kao veleprodaja, pa JIB kupca ide sa prefiksom <strong>VP:</strong>.
                Za stranog kupca bez JIB-a šalje se <strong>VP:9999999999999</strong>.
                Uključi samo ako je uređaj registrovan za veleprodaju — tada je JIB kupca obavezan.
            </p>
        </x-section-block>

        <div class="flex flex-col sm:flex-row gap-3">
            <x-button variant="primary" class="grow !py-3.5 !text-[11px] !uppercase !tracking-[0.2em] !font-black">
                Sačuvaj izmjene
            </x-button>
            <x-button variant="ghost" type="submit" form="test-device" class="!py-3.5">
                Provjeri uređaj
            </x-button>
        </div>
    </form>

    <form id="test-device" method="POST" action="{{ route('settings.fiscal.test') }}" class="hidden">@csrf</form>
@endsection
