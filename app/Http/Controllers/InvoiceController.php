<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentType;
use App\Http\Requests\InvoiceRequest;
use App\Http\Requests\SendInvoiceEmailRequest;
use App\Mail\InvoiceMail;
use App\Models\Article;
use App\Models\Client;
use App\Models\Currency;
use App\Models\FiscalRecord;
use App\Models\Invoice;
use App\Services\FiscalDeviceHealth;
use App\Services\FiscalReceiptStore;
use App\Services\InvoicePdfService;
use App\Services\InvoiceWriter;
use App\Services\MailService;
use App\Settings\DocumentSettings;
use App\Settings\FiscalSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Native\Mobile\Facades\Share;

class InvoiceController extends Controller
{
    public function __construct(
        private InvoiceWriter $writer,
        private DocumentSettings $documents,
        private FiscalSettings $fiscalSettings,
    ) {}

    public function index(Request $request, FiscalDeviceHealth $health)
    {
        $filters = [
            'q' => $request->string('q')->toString(),
            'status' => $request->string('status')->toString(),
            'payment_type' => $request->string('payment_type')->toString(),
            'created_from' => $request->string('created_from')->toString(),
            'created_to' => $request->string('created_to')->toString(),
            'year' => (int) ($request->integer('year') ?: date('Y')),
        ];

        $invoices = Invoice::with('client', 'originalInvoice')
            ->search($filters['q'])
            ->whereBetween('date', ["{$filters['year']}-01-01", "{$filters['year']}-12-31"])
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
            'fiscalHealth' => $health->current(),
        ]);
    }

    /** Godine u kojima postoje računi, uvijek uključujući izabranu i tekuću. */
    private function years(int $selected): array
    {
        // Bez YEAR(): upakovana aplikacija radi na SQLite-u, koji tu funkciju nema.
        return Invoice::query()
            ->distinct()
            ->pluck('date')
            ->map(fn ($date) => (int) $date->format('Y'))
            ->push($selected, (int) date('Y'))
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

        if ($status = InvoiceStatus::tryFrom($filters['status'])) {
            $active[] = [
                'label' => 'Status',
                'value' => $status->label(),
                'clear' => $this->without($filters, ['status']),
            ];
        }

        if ($paymentType = PaymentType::tryFrom($filters['payment_type'])) {
            $active[] = [
                'label' => 'Plaćanje',
                'value' => $paymentType->label(),
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

    public function create()
    {
        return view('invoices.create', $this->formData());
    }

    public function store(InvoiceRequest $request)
    {
        $invoice = $this->writer->create($request->validated());

        return redirect()->route('invoices.show', $invoice)
            ->with('status', "Račun {$invoice->invoice_number} je kreiran.");
    }

    public function show(Invoice $invoice, FiscalDeviceHealth $health)
    {
        $invoice->load(['client', 'items', 'originalInvoice', 'fiscalRecords.receipt']);

        return view('invoices.show', [
            'invoice' => $invoice,
            'fiscalHealth' => $health->current(),
        ]);
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

        return redirect()->route('invoices.show', $invoice)->with('status', 'Izmjene su sačuvane.');
    }

    public function destroy(Invoice $invoice)
    {
        if (! $invoice->isDeletable()) {
            return redirect()
                ->route('invoices.show', $invoice)
                ->with('error', 'Moguće je obrisati samo račune sa statusom Kreiran ili Storno kreiran.');
        }

        $number = $invoice->invoice_number;

        // Storno se briše dok nije fiskalizovan; original se tada vraća u
        // fiskalizovano stanje, inače ostane zaglavljen u „storno kreiran"
        // i novi storno se ne bi mogao napraviti.
        $original = $invoice->originalInvoice;

        $invoice->delete();

        $original?->update(['status' => InvoiceStatus::Fiscalized]);

        return redirect()
            ->route('invoices.index')
            ->with('status', "Račun {$number} je obrisan.");
    }

    /** PDF: browser ga preuzima, a upakovana aplikacija predaje sistemu kao datoteku. */
    public function pdf(Request $request, Invoice $invoice, InvoicePdfService $pdf)
    {
        $mobile = isMobile();

        Log::channel('mobile')->info('Invoice PDF requested', [
            'invoice_id' => $invoice->id,
            'jump' => getenv('JUMP_BRIDGE_PORT') !== false,
            'mobile' => $mobile,
        ]);

        if ($request->boolean('mobile_payload')) {
            $contents = $pdf->contents($invoice);

            return response()->json([
                'mime' => 'application/pdf',
                'filename' => $pdf->filename($invoice),
                'contents' => base64_encode($contents),
            ]);
        }

        if (getenv('JUMP_BRIDGE_PORT') !== false) {
            return $pdf->inline($invoice);
        }

        if (! $mobile) {
            return $pdf->download($invoice);
        }

        $tempDir = config('nativephp-internal.tempdir') ?: storage_path('app/private');
        $path = rtrim($tempDir, '/').'/'.$pdf->filename($invoice);
        @mkdir(dirname($path), 0755, true);
        file_put_contents($path, $pdf->contents($invoice));

        Log::channel('mobile')->info('Invoice PDF handed to native share', [
            'invoice_id' => $invoice->id,
            'bytes' => filesize($path),
        ]);

        Share::file('Račun '.$invoice->invoice_number, 'Račun '.$invoice->invoice_number, $path);

        return $request->expectsJson()
            ? response()->json(['message' => 'PDF je spreman za čuvanje ili dijeljenje.'])
            : redirect()->route('invoices.show', $invoice);
    }

    /** Račun mailom, sa PDF-om i po želji fiskalnim računima kao prilozima. */
    public function email(
        SendInvoiceEmailRequest $request,
        Invoice $invoice,
        MailService $mail,
        InvoicePdfService $pdf,
        FiscalReceiptStore $receipts,
    ) {
        $invoice->load(['client', 'items', 'fiscalRecords.receipt']);

        // Zapis bez sačuvanog računa bi tiho ispao iz priloga, a korisnik bi dobio
        // poruku da je sve poslato — zato se odvaja da odgovor može to reći.
        [$available, $missing] = collect($request->validated('attach_fiscal_record_ids') ?? [])
            ->map(fn ($id) => $invoice->fiscalRecords->firstWhere('id', $id))
            ->filter()
            ->partition(fn ($record) => $receipts->has($record));

        $pdfPath = null;

        if ($request->boolean('attach_pdf')) {
            $pdfPath = storage_path('app/private/racun-'.Str::random(16).'.pdf');
            @mkdir(dirname($pdfPath), 0755, true);
            file_put_contents($pdfPath, $pdf->contents($invoice));
        }

        try {
            [$fromAddress, $fromName] = $mail->from();

            $mail->send($request->validated('to'), new InvoiceMail(
                invoice: $invoice,
                emailSubject: $request->validated('subject'),
                body: $request->validated('body'),
                verificationUrl: $invoice->fiscalRecords->last()?->verification_url,
                pdfPath: $pdfPath,
                attachFiscalRecordIds: $available->pluck('id')->values()->all(),
                fromAddress: $fromAddress,
                fromName: $fromName,
            ));
        } catch (\RuntimeException $e) {
            report($e);

            return response()->json(['message' => 'Slanje nije uspjelo: '.$e->getMessage()], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'Slanje emaila trenutno nije uspjelo. Pokušajte ponovo.'], 422);
        } finally {
            if ($pdfPath && file_exists($pdfPath)) {
                @unlink($pdfPath);
            }
        }

        return response()->json([
            'message' => $missing->isEmpty()
                ? 'Račun je poslat na email.'
                : 'Račun je poslat, ali fiskalni račun nije priložen jer sadržaja nema.',
        ]);
    }

    /** Fiskalni dokument iz privatne pohrane, za modal i za novi tab. */
    public function receipt(Request $request, FiscalRecord $record, FiscalReceiptStore $receipts)
    {
        $record->load('receipt');

        Log::channel('mobile')->info('Fiscal receipt requested', [
            'fiscal_record_id' => $record->id,
            'extension' => $record->receipt?->extension,
            'bytes' => $record->receipt?->size,
            'available' => $receipts->has($record),
        ]);

        if ($request->boolean('mobile_payload')) {
            $contents = $receipts->binary($record);
            abort_if($contents === null, 404, 'Slika fiskalnog računa nije dostupna.');

            return response()->json([
                'mime' => $receipts->mime($record),
                'extension' => $receipts->extension($record),
                'contents' => base64_encode($contents),
            ]);
        }

        return $receipts->response($record);
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
            'defaultTemplate' => $this->documents->template,
            'defaultLanguage' => $this->documents->language,
            'defaultCurrency' => Currency::where('is_default', true)->value('code') ?? 'BAM',
            'defaultDueDays' => $this->documents->invoice_due_days,
            'defaultNotes' => $this->documents->invoice_notes,
            'defaultPaymentType' => $this->fiscalSettings->default_payment_type,
        ];
    }
}
