<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\FiscalDeviceHealth;
use App\Services\FiscalRefundCreator;
use App\Services\FiscalService;
use RuntimeException;
use Throwable;

class FiscalController extends Controller
{
    public function __construct(
        private FiscalService $fiscal,
        private FiscalDeviceHealth $health,
        private FiscalRefundCreator $refunds,
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

    public function createRefund(Invoice $invoice)
    {
        try {
            $refund = $this->refunds->create($invoice);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

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
