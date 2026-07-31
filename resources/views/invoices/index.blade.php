@extends('layouts.app')
@section('title', 'Računi')
@section('actions')<x-create-button :href="route('invoices.create')" label="Novi račun" />@endsection

@section('content')
    <div x-data="invoiceIndex()">
        <x-invoices.filters :filters="$filters" :years="$years" :active-filters="$activeFilters" />
        <x-invoices.list :invoices="$invoices" />

        <x-drawer title="Detalji računa" state="detailDrawer">
            <div x-show="detailLoading" class="flex justify-center py-10">
                <div class="h-6 w-6 border-2 border-primary/30 border-t-primary rounded-full animate-spin"></div>
            </div>
            <div x-show="! detailLoading" x-html="detailHtml"></div>
        </x-drawer>
    </div>
@endsection

@push('scripts')
    <script>
        function invoiceIndex() {
            return {
                yearDrawer: false,
                detailDrawer: false,
                detailLoading: false,
                detailHtml: '',

                // Detalji se dovlače sa servera da bi lista ostala lagana i da bi
                // se prikaz računa držao na jednom mjestu (invoices/detail.blade.php).
                async openDetail(url) {
                    this.detailHtml = '';
                    this.detailLoading = true;
                    this.detailDrawer = true;

                    try {
                        const response = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                        this.detailHtml = response.ok
                            ? await response.text()
                            : '<p class="py-8 text-center text-sm font-bold text-[var(--color-error)]">Detalji nisu dostupni.</p>';
                    } catch {
                        this.detailHtml = '<p class="py-8 text-center text-sm font-bold text-[var(--color-error)]">Detalji nisu dostupni.</p>';
                    } finally {
                        this.detailLoading = false;
                    }
                },
            };
        }
    </script>
@endpush
