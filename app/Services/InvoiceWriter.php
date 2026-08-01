<?php

namespace App\Services;

use App\Models\Article;
use App\Models\FiscalTaxRate;
use App\Models\Invoice;
use App\Settings\DocumentSettings;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Upis računa iz podataka forme.
 *
 * Iznosi se uvijek preračunavaju iz količine i cijene — ono što dođe iz forme nije
 * izvor istine. Cijena je sa porezom, kako OFS očekuje,
 * pa se osnovica i porez izvode iz nje.
 */
class InvoiceWriter
{
    public function __construct(
        private InvoiceNumber $numbers,
        private DocumentSettings $documents,
    ) {}

    public function create(array $data): Invoice
    {
        return DB::transaction(function () use ($data) {
            $invoice = Invoice::create($this->attributes($data) + [
                'invoice_number' => $this->numbers->next((int) Carbon::parse($data['date'])->year),
            ]);

            $this->writeItems($invoice, $data['items']);

            return $invoice;
        });
    }

    public function update(Invoice $invoice, array $data): Invoice
    {
        return DB::transaction(function () use ($invoice, $data) {
            $invoice->update($this->attributes($data));
            $invoice->items()->delete();
            $this->writeItems($invoice, $data['items']);

            return $invoice;
        });
    }

    private function attributes(array $data): array
    {
        return [
            'client_id' => $data['client_id'] ?: null,
            'payment_type' => $data['payment_type'],
            'currency' => $data['currency'],
            'template' => $data['template'],
            'language' => $data['language'],
            'date' => $data['date'],
            'due_date' => $data['due_date'],
            'notes' => $data['notes'] ?? $this->documents->invoice_notes,
        ];
    }

    private function writeItems(Invoice $invoice, array $items): void
    {
        $subtotal = 0;
        $taxTotal = 0;

        $taxRates = FiscalTaxRate::currentBasisPointsByLabel();

        foreach ($items as $row) {
            $line = $this->line($row, $taxRates);

            $invoice->items()->create($line);

            $subtotal += $line['subtotal'];
            $taxTotal += $line['tax_amount'];
        }

        $invoice->update([
            'subtotal' => $subtotal,
            'tax_total' => $taxTotal,
            'total' => $subtotal + $taxTotal,
        ]);

        $this->rememberPrices($items);
    }

    /**
     * Cijena je inkluzivna: osnovica = ukupno / (1 + stopa), porez je ostatak.
     *
     * @param  array<string, int>  $taxRates  label => bazni poeni
     */
    private function line(array $row, array $taxRates): array
    {
        $quantity = max(1, (int) $row['quantity']);
        $unitPrice = (int) round(((float) $row['unit_price']) * 100);
        $taxLabel = $row['tax_label'];
        $taxRate = (int) ($taxRates[$taxLabel] ?? throw new \RuntimeException('Izabrana poreska oznaka više nije dostupna na fiskalnom uređaju.'));

        $total = $quantity * $unitPrice;
        $subtotal = (int) round($total / (1 + $taxRate / 10000));

        return [
            'article_id' => $row['article_id'] ?: null,
            'name' => $row['name'],
            'description' => $row['description'] ?? null,
            'unit' => $row['unit'] ?? 'kom',
            'tax_label' => $taxLabel,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'tax_rate' => $taxRate,
            'subtotal' => $subtotal,
            'tax_amount' => $total - $subtotal,
            'total' => $total,
        ];
    }

    /** Pamti posljednju cijenu artikla za sljedeći unos. */
    private function rememberPrices(array $items): void
    {
        foreach ($items as $row) {
            if (! $row['article_id']) {
                continue;
            }

            Article::whereKey($row['article_id'])
                ->update(['last_unit_price' => (int) round(((float) $row['unit_price']) * 100)]);
        }
    }
}
