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
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

/**
 * Vraća stvarni HTML predloška sa oglednim podacima; galerija ga učitava u iframe.
 */
class DocumentTemplatePreviewController extends Controller
{
    public function __invoke(DocumentTemplate $template, InvoicePdfService $pdf): Response
    {
        $invoice = new Invoice([
            'invoice_number' => '0001/2026',
            'date' => now()->startOfYear(),
            'due_date' => now()->startOfYear()->addDays(15),
            'currency' => 'BAM',
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

            new InvoiceItem([
                'name' => 'Konsultantska usluga2',
                'unit' => Unit::Usl,
                'quantity' => 1,
                'tax_rate' => 900,
                'total' => 11000,
            ]),
            new InvoiceItem([
                'name' => 'Konsultantska usluga3',
                'unit' => Unit::Usl,
                'quantity' => 1,
                'tax_rate' => 900,
                'total' => 10000,

            ]),

            new InvoiceItem([
                'name' => 'Konsultantska usluga4',
                'unit' => Unit::Usl,
                'quantity' => 2,
                'tax_rate' => 900,
                'total' => 10000,

            ]),
            new InvoiceItem([
                'name' => 'Konsultantska usluga5',
                'unit' => Unit::Usl,
                'quantity' => 1,
                'tax_rate' => 900,
                'total' => 10000,

            ]),
        ]));

        return response($pdf->html($invoice, $template));
    }
}
