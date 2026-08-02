<?php

namespace App\Http\Controllers;

use App\Enums\DocumentTemplate;
use App\Enums\PaymentType;
use App\Enums\Unit;
use App\Models\Client;
use App\Models\Currency;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Services\InvoicePdfService;
use App\Settings\CompanySettings;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DocumentTemplatePreviewController extends Controller
{
    public function __invoke(DocumentTemplate $template, CompanySettings $company, InvoicePdfService $pdf): View
    {
        $invoice = new Invoice([
            'invoice_number' => '0001/2026',
            'date' => now()->startOfYear(),
            'due_date' => now()->startOfYear()->addDays(15),
            'currency' => 'BAM',
            'template' => $template,
            'payment_type' => PaymentType::WireTransfer,
            'subtotal' => 9200,
            'tax_total' => 800,
            'total' => 10000,
            'notes' => 'Primjer napomene na računu.',
        ]);

        $invoice->setRelation('client', new Client([
            'name' => 'Primjer kupac d.o.o.',
            'address' => 'Ulica primjera 12',
            'zip' => '78000',
            'city' => 'Banja Luka',
            'vat_id' => '4400000000000',
        ]));
        $invoice->setRelation('currencyDefinition', new Currency(['code' => 'BAM', 'symbol' => 'KM']));
        $invoice->setRelation('fiscalRecords', new Collection);
        $invoice->setRelation('items', new Collection([
            new InvoiceItem([
                'name' => 'Konsultantska usluga',
                'unit' => Unit::Usl,
                'quantity' => 1,
                'tax_rate' => 900,
                'total' => 10000,
            ]),
        ]));

        return view($pdf->viewFor($template), [
            'invoice' => $invoice,
            'company' => $company,
            'bankAccounts' => new Collection,
        ]);
    }
}
