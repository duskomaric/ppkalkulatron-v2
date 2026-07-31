<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentType;
use App\Http\Requests\InvoiceRequest;
use App\Models\Article;
use App\Models\Client;
use App\Models\Currency;
use App\Models\Invoice;
use App\Services\InvoiceWriter;
use App\Settings\DocumentSettings;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function __construct(private InvoiceWriter $writer) {}

    public function index(Request $request)
    {
        $filters = [
            'q' => $request->string('q')->toString(),
            'status' => $request->string('status')->toString(),
            'payment_type' => $request->string('payment_type')->toString(),
            'created_from' => $request->string('created_from')->toString(),
            'created_to' => $request->string('created_to')->toString(),
            'year' => (int) ($request->integer('year') ?: date('Y')),
        ];

        $invoices = Invoice::with('client')
            ->search($filters['q'])
            ->whereYear('date', $filters['year'])
            ->when($filters['status'], fn ($q, $status) => $q->where('status', $status))
            ->when($filters['payment_type'], fn ($q, $type) => $q->where('payment_type', $type))
            ->when($filters['created_from'], fn ($q, $from) => $q->whereDate('created_at', '>=', $from))
            ->when($filters['created_to'], fn ($q, $to) => $q->whereDate('created_at', '<=', $to))
            ->latest('date')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('invoices.index', [
            'invoices' => $invoices,
            'filters' => $filters,
            'years' => $this->years($filters['year']),
            'activeFilters' => $this->activeFilters($filters),
        ]);
    }

    /** Godine u kojima postoje računi, uvijek uključujući izabranu i tekuću. */
    private function years(int $selected): array
    {
        return Invoice::query()
            ->selectRaw('DISTINCT YEAR(`date`) as year')
            ->pluck('year')
            ->push($selected, (int) date('Y'))
            ->map(fn ($year) => (int) $year)
            ->unique()
            ->sortDesc()
            ->values()
            ->all();
    }

    /** Bedževi aktivnih filtera; svaki nosi link koji samo njega uklanja. */
    private function activeFilters(array $filters): array
    {
        $active = [];

        if ($filters['q'] !== '') {
            $active[] = ['label' => 'Pretraga', 'value' => $filters['q'], 'clear' => $this->without($filters, ['q'])];
        }

        if ($filters['status'] !== '') {
            $active[] = [
                'label' => 'Status',
                'value' => InvoiceStatus::from($filters['status'])->label(),
                'clear' => $this->without($filters, ['status']),
            ];
        }

        if ($filters['payment_type'] !== '') {
            $active[] = [
                'label' => 'Plaćanje',
                'value' => PaymentType::from($filters['payment_type'])->label(),
                'clear' => $this->without($filters, ['payment_type']),
            ];
        }

        if ($filters['created_from'] !== '' || $filters['created_to'] !== '') {
            $active[] = [
                'label' => 'Datum',
                'value' => ($filters['created_from'] ?: '—').' → '.($filters['created_to'] ?: '—'),
                'clear' => $this->without($filters, ['created_from', 'created_to']),
            ];
        }

        return $active;
    }

    private function without(array $filters, array $keys): string
    {
        return route('invoices.index', array_merge(
            array_filter($filters, fn ($value, $key) => ! in_array($key, $keys, true) && $value !== '', ARRAY_FILTER_USE_BOTH),
            ['year' => $filters['year']],
        ));
    }

    public function create(Request $request)
    {
        return $this->form($request, $this->formData(), 'invoices.create');
    }

    public function store(InvoiceRequest $request)
    {
        $invoice = $this->writer->create($request->validated());

        return $this->saved($request, $invoice, "Račun {$invoice->invoice_number} je kreiran.");
    }

    public function show(Request $request, Invoice $invoice)
    {
        $invoice->load('client', 'items');

        // Lista otvara detalje u draweru i dovlači samo njegov sadržaj; puna
        // stranica ostaje za direktan link i za rad bez JavaScripta.
        return $request->boolean('partial')
            ? view('invoices.detail', ['invoice' => $invoice])
            : view('invoices.show', ['invoice' => $invoice]);
    }

    public function edit(Request $request, Invoice $invoice)
    {
        if (! $invoice->isDeletable()) {
            return redirect()
                ->route('invoices.show', $invoice)
                ->with('error', 'Fiskalizovan račun se ne može mijenjati.');
        }

        return $this->form($request, $this->formData(['invoice' => $invoice->load('items')]), 'invoices.edit');
    }

    public function update(InvoiceRequest $request, Invoice $invoice)
    {
        if (! $invoice->isDeletable()) {
            return redirect()
                ->route('invoices.show', $invoice)
                ->with('error', 'Fiskalizovan račun se ne može mijenjati.');
        }

        $this->writer->update($invoice, $request->validated());

        return $this->saved($request, $invoice, 'Izmjene su sačuvane.');
    }

    public function destroy(Invoice $invoice)
    {
        if (! $invoice->isDeletable()) {
            return redirect()
                ->route('invoices.show', $invoice)
                ->with('error', 'Moguće je obrisati samo račune sa statusom Kreiran ili Storno kreiran.');
        }

        $number = $invoice->invoice_number;
        $invoice->delete();

        return redirect()
            ->route('invoices.index')
            ->with('status', "Račun {$number} je obrisan.");
    }

    /** Drawer traži samo tijelo forme; puna stranica ostaje za direktan link. */
    private function form(Request $request, array $data, string $view)
    {
        return $request->boolean('partial')
            ? view('invoices.form', $data)
            : view($view, $data);
    }

    /** Iz drawera se šalje preko XHR-a, pa odgovor mora reći kuda dalje. */
    private function saved(Request $request, Invoice $invoice, string $message)
    {
        session()->flash('status', $message);

        if ($request->expectsJson()) {
            return response()->json(['redirect' => route('invoices.index')]);
        }

        return redirect()->route('invoices.show', $invoice);
    }

    private function formData(array $extra = []): array
    {
        return $extra + [
            'invoice' => null,
            'clients' => Client::where('is_active', true)->orderBy('name')
                ->get(['id', 'name', 'email', 'phone']),
            'articles' => Article::where('is_active', true)->orderBy('name')
                ->get(['id', 'name', 'description', 'unit', 'tax_label', 'last_unit_price']),
            'currencies' => Currency::orderByDesc('is_default')->orderBy('code')->get(['code', 'name']),
            'defaultTemplate' => app(DocumentSettings::class)->template,
            'defaultCurrency' => Currency::where('is_default', true)->value('code') ?? 'BAM',
            'defaultDueDays' => app(DocumentSettings::class)->invoice_due_days,
            'defaultNotes' => app(DocumentSettings::class)->invoice_notes,
        ];
    }
}
