<?php

namespace App\Http\Controllers;

use App\Http\Requests\InvoiceRequest;
use App\Models\Article;
use App\Models\Client;
use App\Models\Invoice;
use App\Services\InvoiceWriter;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function __construct(private InvoiceWriter $writer) {}

    public function index(Request $request)
    {
        $invoices = Invoice::with('client')
            ->search($request->string('q')->toString())
            ->latest('date')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('invoices.index', [
            'invoices' => $invoices,
            'q' => $request->string('q')->toString(),
        ]);
    }

    public function create()
    {
        return view('invoices.create', $this->formData());
    }

    public function store(InvoiceRequest $request)
    {
        $invoice = $this->writer->create($request->validated());

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('status', "Račun {$invoice->invoice_number} je kreiran.");
    }

    public function show(Invoice $invoice)
    {
        return view('invoices.show', ['invoice' => $invoice->load('client', 'items')]);
    }

    public function edit(Invoice $invoice)
    {
        if (! $invoice->isDeletable()) {
            return redirect()
                ->route('invoices.show', $invoice)
                ->with('error', 'Fiskalizovan račun se ne može mijenjati.');
        }

        return view('invoices.edit', $this->formData(['invoice' => $invoice->load('items')]));
    }

    public function update(InvoiceRequest $request, Invoice $invoice)
    {
        if (! $invoice->isDeletable()) {
            return redirect()
                ->route('invoices.show', $invoice)
                ->with('error', 'Fiskalizovan račun se ne može mijenjati.');
        }

        $this->writer->update($invoice, $request->validated());

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('status', 'Izmjene su sačuvane.');
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

    private function formData(array $extra = []): array
    {
        return $extra + [
            'invoice' => null,
            'clients' => Client::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'articles' => Article::where('is_active', true)->orderBy('name')
                ->get(['id', 'name', 'unit', 'tax_label', 'last_unit_price']),
        ];
    }
}
