<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class FiscalRefundCreator
{
    public function __construct(private InvoiceNumber $numbers) {}

    /** Storno račun preuzima stavke i iznose originalnog računa. */
    public function create(Invoice $invoice): Invoice
    {
        if ($invoice->refund_invoice_id) {
            throw new RuntimeException('Storno za ovaj račun već postoji.');
        }

        if ($invoice->originalInvoice()->exists()) {
            throw new RuntimeException('Storno računa se ne može stornirati.');
        }

        if ($invoice->status !== InvoiceStatus::Fiscalized) {
            throw new RuntimeException('Storno se pravi samo od fiskalizovanog računa.');
        }

        return DB::transaction(function () use ($invoice): Invoice {
            $invoice->load('items');

            $refund = Invoice::create([
                'invoice_number' => $this->numbers->next((int) $invoice->date->year),
                'client_id' => $invoice->client_id,
                'status' => InvoiceStatus::RefundCreated,
                'date' => $invoice->date,
                'due_date' => $invoice->due_date,
                'notes' => $invoice->notes,
                'currency' => $invoice->currency,
                'language' => $invoice->language,
                'payment_type' => $invoice->payment_type,
                'subtotal' => abs($invoice->subtotal),
                'tax_total' => abs($invoice->tax_total),
                'discount_total' => abs($invoice->discount_total),
                'total' => abs($invoice->total),
            ]);

            $refund->items()->createMany($invoice->items->map(fn ($item): array => [
                'article_id' => $item->article_id,
                'name' => $item->name,
                'description' => $item->description,
                'unit' => $item->unit,
                'quantity' => $item->quantity,
                'unit_price' => abs($item->unit_price),
                'subtotal' => abs($item->subtotal),
                'tax_rate' => $item->tax_rate,
                'tax_label' => $item->tax_label,
                'tax_amount' => abs($item->tax_amount),
                'total' => abs($item->total),
            ])->all());

            // Original ostaje fiskalizovan; storniranje je stanje samog storno dokumenta.
            $invoice->update(['refund_invoice_id' => $refund->id]);

            return $refund;
        });
    }
}
