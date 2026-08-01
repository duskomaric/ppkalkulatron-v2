@extends('layouts.app')
@section('title', 'Fiskalizacija')

@section('content')
    <x-back-link :href="route('invoices.index')" />

    <form id="fiscal-settings-form" method="POST" action="{{ route('settings.fiscal.update') }}" class="max-w-3xl space-y-8 animate-fade-in"
          x-data="{ layout: '{{ old('receipt_layout', $settings->receipt_layout) }}', deviceMode: '{{ old('device_mode', $settings->device_mode) }}' }">
        @csrf
        @method('PUT')

        <x-section-block variant="card" class="sm:p-8 space-y-6">
            <x-section-header icon="file-text" title="Uređaj" :help="route('help').'#fiskalizacija'" />

            <div class="space-y-2">
                <p class="text-[10px] font-black uppercase tracking-[0.18em] text-[var(--color-text-dim)]">Način uređaja</p>
                <input type="hidden" name="device_mode" :value="deviceMode">
                <div class="grid grid-cols-2 gap-2" role="radiogroup" aria-label="Način uređaja">
                    <button type="button" x-on:click="deviceMode = 'local'" :aria-pressed="deviceMode === 'local'"
                            :class="deviceMode === 'local' ? 'border-primary/50 bg-primary/10 text-primary' : 'border-[var(--color-border)] bg-[var(--color-surface)] text-[var(--color-text-muted)]'"
                            class="cursor-pointer rounded-xl border p-3 text-left transition-colors">
                        <span class="block text-xs font-black">Lokalni ESIR</span>
                        <span class="mt-1 block text-[10px] leading-snug text-[var(--color-text-dim)]">Kasa na istoj Wi‑Fi/LAN mreži</span>
                    </button>
                    <button type="button" x-on:click="deviceMode = 'cloud'" :aria-pressed="deviceMode === 'cloud'"
                            :class="deviceMode === 'cloud' ? 'border-primary/50 bg-primary/10 text-primary' : 'border-[var(--color-border)] bg-[var(--color-surface)] text-[var(--color-text-muted)]'"
                            class="cursor-pointer rounded-xl border p-3 text-left transition-colors">
                        <span class="block text-xs font-black">OFS Cloud</span>
                        <span class="mt-1 block text-[10px] leading-snug text-[var(--color-text-dim)]">pos.ofs.ba test ili cloud uređaj</span>
                    </button>
                </div>
            </div>

            <x-form-input label="Base URL" name="base_url" :value="$settings->base_url" required icon="cog"
                          hint="Cloud: https://pos.ofs.ba — lokalni: http://192.168.x.x:3566" />

            <x-form-input label="API ključ" name="api_key" :value="$settings->api_key" icon="lock" />

            <div x-cloak x-show="deviceMode === 'cloud'" x-transition.opacity class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-form-input label="Serijski broj" name="serial_number" :value="$settings->serial_number"
                              hint="Samo za cloud." />
                <x-form-input label="PAK" name="pac" :value="$settings->pac" hint="Samo za cloud." />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-form-input label="Blagajnik" name="cashier" :value="$settings->cashier" required
                              hint="Ime koje se ispisuje na fiskalnom računu." />
            </div>

            <div class="pt-2 border-t border-[var(--color-border)]">
                <div class="flex items-center gap-2">
                    <x-button variant="ghost" type="submit" form="test-device" class="w-full sm:w-auto !py-3.5">
                        Provjeri uređaj
                    </x-button>
                    <x-fiscal-health-indicator :health="$fiscalHealth" :url="route('settings.fiscal.status', [], false)" />
                </div>
            </div>
        </x-section-block>

        <x-section-block variant="card" class="sm:p-8 space-y-6">
            <x-section-header icon="file-text" title="Štampa računa" :help="route('help').'#stampa-racuna'" />

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-form-select label="Izgled računa" name="receipt_layout" :value="$settings->receipt_layout" required
                               x-model="layout" :options="['Slip' => 'Slip (termalni)', 'Invoice' => 'Invoice (A4)']" />

                <x-form-select label="Format fiskalnog dokumenta" name="receipt_document_format" required
                               x-effect="layout === 'Invoice' && $el.value === 'Png' && ($el.value = 'Pdf')">
                    {{-- A4 nema PNG: uređaj vrati praznu jednopikselnu sliku. --}}
                    {{-- template x-if, ne x-show: sakriven <option> ostaje izabran, a
                     mobilni Safari display:none na opciji ionako ignoriše. --}}
                        <template x-if="layout !== 'Invoice'">
                            <option value="Png" @selected(old('receipt_document_format', $settings->receipt_document_format) === 'Png')>Png</option>
                        </template>
                    <option value="Pdf" @selected(old('receipt_document_format', $settings->receipt_document_format) === 'Pdf')>Pdf</option>
                    <option value="Html" @selected(old('receipt_document_format', $settings->receipt_document_format) === 'Html')>Html</option>
                </x-form-select>
            </div>

            <x-form-select label="Podrazumijevani način plaćanja" name="default_payment_type"
                           :value="$settings->default_payment_type" required :options="\App\Enums\PaymentType::options()" />

            <x-form-textarea label="Linije u zaglavlju računa" name="receipt_header_text_lines" rows="3"
                             :value="implode(PHP_EOL, $settings->receipt_header_text_lines)" />

            <x-toggle name="print_receipt" :checked="$settings->print_receipt" label="Štampaj račun pri fiskalizaciji" />
        </x-section-block>

        <x-section-block variant="card" class="sm:p-8 space-y-6">
            <x-section-header icon="hash" title="Veleprodaja" :help="route('help').'#fiskalizacija'" />

            <x-toggle name="wholesale" :checked="$settings->wholesale" label="Veleprodaja (VP)" />

            <p class="text-[11px] text-[var(--color-text-dim)] pl-1 leading-relaxed">
                Promet se evidentira kao veleprodaja, pa JIB kupca ide sa prefiksom <strong>VP:</strong>.
                Za stranog kupca bez JIB-a šalje se <strong>VP:9999999999999</strong>.
                Uključi samo ako je uređaj registrovan za veleprodaju — tada je JIB kupca obavezan.
            </p>
        </x-section-block>

    </form>

    <form id="test-device" method="POST" action="{{ route('settings.fiscal.test') }}" class="hidden">@csrf</form>

    {{-- Servisne radnje prema uređaju, van forme sa podešavanjima. --}}
    <div class="mt-8 space-y-8 max-w-3xl">
        <x-section-block variant="card" x-data="networkScan()">
            <x-section-header icon="search" title="Skeniranje mreže" :help="route('help').'#skeniranje'" />

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
            <x-section-header icon="lock" title="PIN sigurnosnog elementa" :help="route('help').'#fiskalizacija'" />

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
            <x-section-header icon="search" title="Potraga po RequestId" :help="route('help').'#fiskalizacija'" />

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

    <div class="mt-8 pb-4 max-w-3xl">
        <x-button variant="primary" type="submit" form="fiscal-settings-form"
                  class="w-full !py-3.5 !text-[11px] !uppercase !tracking-[0.2em] !font-black">
            Sačuvaj izmjene
        </x-button>
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
                        const response = await fetch(@js(route('settings.fiscal.scan', [], false)), {
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
