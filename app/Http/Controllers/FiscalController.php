<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Services\FiscalService;
use App\Services\InvoiceNumber;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class FiscalController extends Controller
{
    public function __construct(private FiscalService $fiscal) {}

    public function fiscalize(Invoice $invoice)
    {
        return $this->run(fn () => $this->fiscal->fiscalize($invoice), 'Račun je fiskalizovan.');
    }

    public function copy(Invoice $invoice)
    {
        return $this->run(fn () => $this->fiscal->copy($invoice), 'Kopija je odštampana.');
    }

    public function refund(Invoice $invoice)
    {
        return $this->run(fn () => $this->fiscal->refund($invoice), 'Storno je fiskalizovan.');
    }

    /** Storno račun: kopija originala sa istim iznosima, kao u v1. */
    public function createRefund(Invoice $invoice, InvoiceNumber $numbers)
    {
        if ($invoice->refund_invoice_id) {
            return response()->json(['message' => 'Storno za ovaj račun već postoji.'], 422);
        }

        if ($invoice->status !== InvoiceStatus::Fiscalized) {
            return response()->json(['message' => 'Storno se pravi samo od fiskalizovanog računa.'], 422);
        }

        $refund = DB::transaction(function () use ($invoice, $numbers) {
            $invoice->load('items');

            $refund = Invoice::create([
                'invoice_number' => $numbers->next(),
                'client_id' => $invoice->client_id,
                'status' => InvoiceStatus::RefundCreated,
                'date' => $invoice->date,
                'due_date' => $invoice->due_date,
                'notes' => $invoice->notes,
                'currency' => $invoice->currency,
                'template' => $invoice->template,
                'language' => $invoice->language,
                'payment_type' => $invoice->payment_type,
                'subtotal' => abs($invoice->subtotal),
                'tax_total' => abs($invoice->tax_total),
                'discount_total' => abs($invoice->discount_total),
                'total' => abs($invoice->total),
            ]);

            $refund->items()->createMany($invoice->items->map(fn ($item) => [
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

            $invoice->update(['refund_invoice_id' => $refund->id, 'status' => InvoiceStatus::RefundCreated]);

            return $refund;
        });

        return response()->json([
            'message' => "Storno račun {$refund->invoice_number} je kreiran.",
            'invoice_id' => $refund->id,
        ]);
    }

    private function run(callable $action, string $message)
    {
        try {
            $action();
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            report($e);

            return response()->json(['message' => 'Greška: '.$e->getMessage()], 500);
        }

        return response()->json(['message' => $message]);
    }
}
