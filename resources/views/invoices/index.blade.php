@extends('layouts.app')
@section('title', 'Računi')

{{-- Dugme je u zaglavlju, van Alpine opsega liste, pa se drawer otvara događajem. --}}
@section('actions')
    <x-create-button label="Novi račun" x-on:click="$dispatch('open-invoice-form')" />
@endsection

@section('content')
    <div x-data="invoiceIndex()"
         x-on:open-invoice-form.window="openForm(@js(route('invoices.create', ['partial' => 1])), 'Novi račun')">
        <x-invoices.filters :filters="$filters" :years="$years" :active-filters="$activeFilters" />
        <x-invoices.list :invoices="$invoices" />

        <x-drawer title="Detalji računa" state="detailDrawer">
            <div x-show="detailLoading" class="flex justify-center py-10">
                <div class="h-6 w-6 border-2 border-primary/30 border-t-primary rounded-full animate-spin"></div>
            </div>
            <div x-show="! detailLoading" x-html="detailHtml"></div>
        </x-drawer>

        <x-email-modal />

        <x-drawer title="Novi račun" state="formDrawer" title-expr="formTitle">
            <div x-show="formLoading" class="flex justify-center py-10">
                <div class="h-6 w-6 border-2 border-primary/30 border-t-primary rounded-full animate-spin"></div>
            </div>
            <div x-show="! formLoading" x-html="formHtml"></div>
        </x-drawer>
    </div>
@endsection

@push('scripts')
    <script>
        function invoiceIndex() {
            const failure = '<p class="py-8 text-center text-sm font-bold text-[var(--color-error)]">Nije moguće učitati. Pokušajte ponovo.</p>';

            return {
                yearDrawer: false,

                detailDrawer: false,
                detailLoading: false,
                detailHtml: '',

                formDrawer: false,
                formLoading: false,
                formHtml: '',
                formTitle: 'Novi račun',
                formErrors: {},
                saving: false,

                emailModal: false,
                emailSending: false,
                emailError: '',
                emailUrl: '',
                emailReceipts: [],
                emailForm: { to: '', subject: '', body: '', attach_pdf: true, attach_fiscal_record_ids: [] },

                // Detalji i forma se dovlače sa servera pa u v2 postoji samo jedan
                // izvor izgleda računa — isti Blade koji servira i puna stranica.
                async openDetail(url) {
                    this.detailHtml = '';
                    this.detailLoading = true;
                    this.detailDrawer = true;
                    this.detailHtml = await this.load(url, failure);
                    this.detailLoading = false;
                },

                async openForm(url, title) {
                    this.formTitle = title;
                    this.formErrors = {};
                    this.formHtml = '';
                    this.formLoading = true;
                    this.detailDrawer = false;
                    this.formDrawer = true;
                    this.formHtml = await this.load(url, failure);
                    this.formLoading = false;
                },

                openEmail(defaults) {
                    this.emailError = '';
                    this.emailUrl = defaults.url;
                    this.emailReceipts = defaults.receipts;
                    this.emailForm = {
                        to: defaults.to,
                        subject: defaults.subject,
                        body: defaults.body,
                        attach_pdf: true,
                        attach_fiscal_record_ids: defaults.receipts.map((record) => record.id),
                    };
                    this.emailModal = true;
                },

                async sendEmail() {
                    if (this.emailSending) return;

                    this.emailSending = true;
                    this.emailError = '';

                    try {
                        const response = await fetch(this.emailUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            },
                            body: JSON.stringify(this.emailForm),
                        });

                        const data = await response.json().catch(() => ({}));

                        if (! response.ok) {
                            this.emailError = data.message || Object.values(data.errors || {})[0]?.[0]
                                || 'Slanje nije uspjelo.';

                            return;
                        }

                        this.emailModal = false;
                        this.detailDrawer = false;
                        this.flash(data.message);
                    } catch {
                        this.emailError = 'Slanje nije uspjelo.';
                    } finally {
                        this.emailSending = false;
                    }
                },

                /** Poruka poslije radnje iz drawera; stranica se ne osvježava. */
                flash(message) {
                    window.dispatchEvent(new CustomEvent('app-flash', { detail: message }));
                },

                closeForm() {
                    this.formDrawer = false;
                },

                async load(url, fallback) {
                    try {
                        const response = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });

                        return response.ok ? await response.text() : fallback;
                    } catch {
                        return fallback;
                    }
                },

                /** Čuvanje iz drawera: greške se prikazuju u formi, uspjeh vodi na listu. */
                async submitForm(event) {
                    if (this.saving) return;

                    this.saving = true;
                    this.formErrors = {};

                    try {
                        const form = event.target;
                        const response = await fetch(form.action, {
                            method: 'POST',
                            body: new FormData(form),
                            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                        });

                        if (response.status === 422) {
                            this.formErrors = (await response.json()).errors || {};
                            this.$el.querySelector('[data-error-summary]')?.scrollIntoView({ behavior: 'smooth', block: 'center' });

                            return;
                        }

                        if (! response.ok) {
                            this.formErrors = { _: ['Čuvanje nije uspjelo. Pokušajte ponovo.'] };

                            return;
                        }

                        window.location = (await response.json()).redirect;
                    } catch {
                        this.formErrors = { _: ['Čuvanje nije uspjelo. Pokušajte ponovo.'] };
                    } finally {
                        this.saving = false;
                    }
                },
            };
        }
    </script>
@endpush
