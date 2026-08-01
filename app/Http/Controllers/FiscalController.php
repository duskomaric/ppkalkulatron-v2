<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Services\FiscalDeviceHealth;
use App\Services\FiscalService;
use App\Services\InvoiceNumber;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class FiscalController extends Controller
{
    public function __construct(
        private FiscalService $fiscal,
        private FiscalDeviceHealth $health,
    ) {}

    public function fiscalize(Invoice $invoice)
    {
        return $this->run(fn () => $this->fiscal->fiscalize($invoice), 'Račun je fiskalizovan.', $invoice);
    }

    public function copy(Invoice $invoice)
    {
        return $this->run(fn () => $this->fiscal->copy($invoice), 'Kopija je odštampana.', $invoice);
    }

    public function refund(Invoice $invoice)
    {
        return $this->run(fn () => $this->fiscal->refund($invoice), 'Storno je fiskalizovan.', $invoice);
    }

    /** Storno račun preuzima stavke i iznose originalnog računa. */
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
                'invoice_number' => $numbers->next((int) $invoice->date->year),
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

    private function run(callable $action, string $message, Invoice $invoice)
    {
        try {
            $action();
            $this->health->markReady();
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            report($e);
            $this->health->markUnavailable();

            return response()->json(['message' => 'Fiskalizacija trenutno nije uspjela. Pokušajte ponovo.'], 500);
        }

        return response()->json(['message' => $message, 'invoice_id' => $invoice->id]);
    }
}
