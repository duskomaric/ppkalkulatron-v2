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
                <x-form-input label="Blagajnik" name="cashier" :value="$settings->cashier" required
                              hint="Ime koje se ispisuje na fiskalnom računu." />
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
                                x-effect="layout === 'Invoice' && $el.value === 'Png' && ($el.value = 'Pdf')"
                                class="w-full h-12 px-4 bg-[var(--color-bg)] border border-[var(--color-border)] rounded-xl focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all font-bold text-sm appearance-none cursor-pointer">
                            {{-- A4 nema PNG: uređaj vrati praznu jednopikselnu sliku. --}}
                            {{-- template x-if, ne x-show: sakriven <option> ostaje izabran, a
                             mobilni Safari display:none na opciji ionako ignoriše. --}}
                        <template x-if="layout !== 'Invoice'">
                            <option value="Png" @selected(old('receipt_image_format', $settings->receipt_image_format) === 'Png')>Png</option>
                        </template>
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
                             :value="implode(PHP_EOL, $settings->receipt_header_text_lines)" />

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

    {{-- Servisne radnje prema uređaju, van forme sa podešavanjima. --}}
    <div class="mt-8 space-y-8 max-w-3xl">
        <x-section-block variant="card" x-data="networkScan()">
            <x-section-header icon="search" title="Skeniranje mreže" />

            <p class="text-[11px] text-[var(--color-text-dim)] pl-1 leading-relaxed">
                Traži ESIR na portu {{ \App\Services\NetworkScanner::PORT }}. Opseg se čita sa mrežnog
                interfejsa uređaja, pa ga ne morate unositi — polje ispod je za slučaj da je kasa
                na drugoj podmreži.
            </p>

            <div class="flex flex-col sm:flex-row gap-3 sm:items-end">
                <div class="grow">
                    <x-form-input label="Opseg (opciono)" name="scan_range" x-model="range"
                                  placeholder="192.168.31.100-105" />
                </div>
                <x-button variant="ghost" type="button" class="!py-3.5 shrink-0" x-on:click="run()"
                          ::disabled="scanning">
                    <span x-show="scanning" x-cloak class="animate-spin rounded-full h-4 w-4 border-2 border-current border-t-transparent"></span>
                    <span x-text="scanning ? 'Skeniram...' : 'Skeniraj mrežu'"></span>
                </x-button>
            </div>

            <template x-if="devices.length">
                <div class="space-y-1.5 pt-2">
                    <template x-for="device in devices" :key="device">
                        <button type="button" x-on:click="use(device)"
                                class="w-full flex items-center justify-between gap-3 px-3 py-2.5 rounded-xl border border-[var(--color-border)] bg-[var(--color-surface)] hover:border-primary/40 transition-all cursor-pointer">
                            <span class="text-xs font-bold text-[var(--color-text-main)]" x-text="device"></span>
                            <span class="text-[10px] font-black uppercase tracking-widest text-primary">Koristi</span>
                        </button>
                    </template>
                </div>
            </template>
        </x-section-block>

        <x-section-block variant="card">
            <x-section-header icon="lock" title="PIN sigurnosnog elementa" />

            <p class="text-[11px] text-[var(--color-text-dim)] pl-1 leading-relaxed">
                Lokalni uređaj traži PIN poslije uključivanja. Dok ga ne dobije, fiskalizacija ne prolazi.
            </p>

            <form method="POST" action="{{ route('settings.fiscal.pin') }}" class="flex flex-col sm:flex-row gap-3 sm:items-end">
                @csrf
                <div class="grow">
                    <x-form-input label="PIN" name="security_pin" type="password" inputmode="numeric"
                                  maxlength="4" autocomplete="off" required hint="Četiri cifre." />
                </div>
                <x-button variant="ghost" class="!py-3.5 shrink-0">Pošalji PIN</x-button>
            </form>
        </x-section-block>

        <x-section-block variant="card">
            <x-section-header icon="search" title="Potraga po RequestId" />

            <p class="text-[11px] text-[var(--color-text-dim)] pl-1 leading-relaxed">
                Ako je veza pukla usred fiskalizacije, uređaj i dalje zna šta je snimio.
                Unesi RequestId iz fiskalnog zapisa da provjeriš je li račun prošao.
            </p>

            <form method="POST" action="{{ route('settings.fiscal.find-request') }}" class="flex flex-col sm:flex-row gap-3 sm:items-end">
                @csrf
                <div class="grow">
                    <x-form-input label="RequestId" name="request_id" maxlength="32" required />
                </div>
                <x-button variant="ghost" class="!py-3.5 shrink-0">Provjeri</x-button>
            </form>
        </x-section-block>
    </div>
@endsection

@push('scripts')
    <script>
        function networkScan() {
            return {
                range: '',
                scanning: false,
                devices: [],

                async run() {
                    if (this.scanning) return;

                    this.scanning = true;
                    this.devices = [];

                    try {
                        const response = await fetch(@js(route('settings.fiscal.scan')), {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            },
                            body: JSON.stringify({ range: this.range }),
                        });

                        const data = await response.json().catch(() => ({}));
                        this.devices = data.devices || [];

                        window.dispatchEvent(new CustomEvent('app-flash', {
                            detail: { message: data.message || 'Skeniranje nije uspjelo.', type: response.ok ? 'success' : 'error' },
                        }));
                    } catch {
                        window.dispatchEvent(new CustomEvent('app-flash', {
                            detail: { message: 'Skeniranje nije uspjelo.', type: 'error' },
                        }));
                    } finally {
                        this.scanning = false;
                    }
                },

                /** Upiši adresu u Base URL i vrati korisnika na to polje. */
                use(device) {
                    const field = document.querySelector('input[name=base_url]');
                    field.value = device;
                    field.dispatchEvent(new Event('input'));
                    field.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    field.focus();
                },
            };
        }
    </script>
@endpush
