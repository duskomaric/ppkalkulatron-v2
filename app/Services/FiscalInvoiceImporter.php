<?php

namespace App\Services;

use App\Enums\FiscalRecordType;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentType;
use App\Enums\Unit;
use App\Models\Article;
use App\Models\Client;
use App\Models\FiscalRecord;
use App\Models\FiscalTaxRate;
use App\Models\Invoice;
use App\Settings\DocumentSettings;
use App\Settings\FiscalSettings;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * Uvoz izdatih računa sa fiskalne kase.
 *
 * Kasa vraća stavke, iznose, poreske oznake, način plaćanja i JIB kupca, ali ne
 * zna naziv kupca, jedinicu mjere ni vezu na naš artikal. Zato se klijent i artikal
 * prvo traže lokalno, pa se prave od onoga što kasa daje — korisnik ih dopunjava.
 *
 * Kopije, obuka i predračuni se preskaču: to nisu dokumenti prometa.
 */
class FiscalInvoiceImporter
{
    /** Račun se uvozi samo ako je stvarni promet. */
    private const IMPORTABLE_TYPE = 'Normal';

    public function __construct(
        private OFSService $ofs,
        private FiscalSettings $settings,
        private DocumentSettings $documents,
        private InvoiceNumber $numbers,
        private FiscalReceiptStore $receipts,
        private Diagnostics $diagnostics,
    ) {}

    /**
     * Spisak računa sa kase u zadatom periodu, uz oznaku šta je već uvezeno.
     *
     * @return array{invoices: array<int, array<string, mixed>>, skipped: int}
     */
    public function search(string $from, string $to): array
    {
        $response = $this->ofs->searchInvoices(['fromDate' => $from, 'toDate' => $to]);

        if (! $response->successful()) {
            throw new RuntimeException('Kasa nije vratila spisak računa. Provjerite vezu i period, pa pokušajte ponovo.');
        }

        $rows = $this->parseCsv((string) $response->body());
        $importable = array_values(array_filter($rows, fn (array $row): bool => $row['type'] === self::IMPORTABLE_TYPE));
        $imported = $this->importedNumbers(array_column($importable, 'number'));

        $invoices = array_map(fn (array $row): array => $row + [
            'imported' => in_array($row['number'], $imported, true),
        ], $importable);

        usort($invoices, fn (array $a, array $b): int => strcmp($a['issued_at'], $b['issued_at']));

        return ['invoices' => $invoices, 'skipped' => count($rows) - count($importable)];
    }

    /**
     * Uvozi zadate fiskalne račune. Storna se vežu na original, pa se ide
     * hronološki — original je do storna već uvezen.
     *
     * @param  array<int, string>  $numbers  fiskalni brojevi računa
     * @param  bool  $useFiscalNumbers  broj računa preuzeti sa kase umjesto iz naše numeracije
     * @return array{imported: int, skipped: int, failed: array<int, array{number: string, message: string}>}
     */
    public function import(array $numbers, bool $useFiscalNumbers = false): array
    {
        $imported = 0;
        $skipped = 0;
        $failed = [];

        foreach ($numbers as $number) {
            try {
                $result = $this->importOne((string) $number, $useFiscalNumbers);
                $result ? $imported++ : $skipped++;
            } catch (Throwable $exception) {
                $this->diagnostics->error('Uvoz računa sa kase nije uspio', [
                    'invoice_number' => $number,
                    'error' => $exception->getMessage(),
                ]);

                $failed[] = ['number' => (string) $number, 'message' => $exception->getMessage()];
            }
        }

        return ['imported' => $imported, 'skipped' => $skipped, 'failed' => $failed];
    }

    /** @return bool `false` kad je račun već uvezen ili nije promet */
    private function importOne(string $number, bool $useFiscalNumbers): bool
    {
        if ($this->importedNumbers([$number]) !== []) {
            return false;
        }

        $response = $this->ofs->getInvoice($number, $this->imageQuery());

        if (! $response->successful()) {
            throw new RuntimeException('Kasa nije vratila sadržaj računa '.$number.'.');
        }

        $data = (array) $response->json();
        $request = (array) ($data['invoiceRequest'] ?? []);
        $result = (array) ($data['invoiceResponse'] ?? []);

        if (($request['invoiceType'] ?? null) !== self::IMPORTABLE_TYPE) {
            return false;
        }

        $isRefund = ($request['transactionType'] ?? 'Sale') === 'Refund';
        $issuedAt = Carbon::parse($result['sdcDateTime'] ?? now());
        $items = $this->lines((array) ($request['items'] ?? []), (array) ($result['taxItems'] ?? []));

        if ($items === []) {
            throw new RuntimeException('Račun '.$number.' nema stavke.');
        }

        // Original ide prvi: tako nosi manji broj i storno ima šta da poništi.
        $referentNumber = $isRefund ? ($request['referentDocumentNumber'] ?? null) : null;

        if ($referentNumber && ! $this->invoiceByFiscalNumber($referentNumber)) {
            $this->importOne($referentNumber, $useFiscalNumbers);
        }

        return DB::transaction(function () use ($request, $result, $data, $number, $useFiscalNumbers, $isRefund, $issuedAt, $items, $referentNumber): bool {
            $invoiceNumber = $useFiscalNumbers
                ? $number
                : $this->numbers->next((int) $issuedAt->year);

            // Brojevi sa kase mogu se poklopiti sa postojećim računom — korisnik je
            // upozoren da se u tom slučaju stari račun prepisuje.
            Invoice::query()->where('invoice_number', $invoiceNumber)->get()->each->delete();

            $invoice = Invoice::create([
                'invoice_number' => $invoiceNumber,
                'client_id' => $this->client($request['buyerId'] ?? null)?->id,
                'status' => $isRefund ? InvoiceStatus::Refunded : InvoiceStatus::Fiscalized,
                'date' => $issuedAt->toDateString(),
                'due_date' => $issuedAt->copy()->addDays(max(0, $this->documents->invoice_due_days))->toDateString(),
                'currency' => 'BAM',
                'language' => $this->documents->language,
                'payment_type' => $this->paymentType($request),
                'imported_at' => now(),
                'subtotal' => array_sum(array_column($items, 'subtotal')),
                'tax_total' => array_sum(array_column($items, 'tax_amount')),
                'total' => array_sum(array_column($items, 'total')),
            ]);

            $invoice->items()->createMany($items);

            $record = $invoice->fiscalRecords()->create([
                'type' => $isRefund ? FiscalRecordType::Refund : FiscalRecordType::Original,
                'fiscal_invoice_number' => $result['invoiceNumber'] ?? $number,
                'fiscal_counter' => isset($result['invoiceCounter']) ? (string) $result['invoiceCounter'] : null,
                'verification_url' => $result['verificationUrl'] ?? null,
                'fiscalized_at' => $issuedAt,
            ]);

            $this->storeReceipt($record, $data, $result);

            if ($isRefund) {
                $this->linkRefund($invoice, $referentNumber);
            }

            return true;
        });
    }

    /**
     * Stavke sa kase: cijena je sa porezom, a stopa dolazi iz poreskih stavki
     * računa — ne iz trenutnih stopa uređaja, jer stariji račun može nositi stopu
     * koja je u međuvremenu promijenjena.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @param  array<int, array<string, mixed>>  $taxItems
     * @return array<int, array<string, mixed>>
     */
    private function lines(array $items, array $taxItems): array
    {
        $rates = [];

        foreach ($taxItems as $tax) {
            if (isset($tax['label'])) {
                $rates[(string) $tax['label']] = (int) round(((float) ($tax['rate'] ?? 0)) * 100);
            }
        }

        $localRates = FiscalTaxRate::basisPointsByLabel();

        return array_map(function (array $item) use ($rates, $localRates): array {
            $label = (string) (($item['labels'] ?? [])[0] ?? '');
            $taxRate = $rates[$label] ?? $localRates[$label] ?? 0;

            $total = $this->pfening($item['totalAmount'] ?? 0);
            $subtotal = (int) round($total / (1 + $taxRate / 10000));
            [$name, $unit] = $this->nameAndUnit((string) ($item['name'] ?? 'Stavka'));
            $article = $this->article($item, $label, $name, $unit);

            return [
                'article_id' => $article?->id,
                'name' => $name,
                'description' => null,
                'unit' => $unit ?? $article?->unit ?? Unit::Kom,
                'tax_label' => $label,
                'quantity' => (float) ($item['quantity'] ?? 1),
                'unit_price' => $this->pfening($item['unitPrice'] ?? 0),
                'tax_rate' => $taxRate,
                'subtotal' => $subtotal,
                'tax_amount' => $total - $subtotal,
                'total' => $total,
            ];
        }, $items);
    }

    /**
     * Aplikacija kasi šalje naziv u obliku „naziv / jm", pa se pri uvozu jedinica
     * vraća u svoje polje umjesto da ostane zalijepljena na naziv artikla.
     *
     * @return array{0: string, 1: ?Unit}
     */
    private function nameAndUnit(string $raw): array
    {
        $name = trim($raw);

        if (! str_contains($name, '/')) {
            return [$name, null];
        }

        $suffix = trim((string) mb_strrchr($name, '/'), '/ ');
        $unit = Unit::tryFrom(mb_strtolower($suffix));

        if (! $unit) {
            return [$name, null];
        }

        return [trim(mb_substr($name, 0, mb_strrpos($name, '/'))) ?: $name, $unit];
    }

    /** Artikal se prvo traži po barkodu, pa po nazivu; ako ga nema, pravi se od podataka sa kase. */
    private function article(array $item, string $taxLabel, string $name, ?Unit $unit): ?Article
    {
        $gtin = preg_replace('/\D/', '', (string) ($item['gtin'] ?? '')) ?: null;

        $existing = $gtin ? Article::query()->where('gtin', $gtin)->first() : null;
        $existing ??= $name !== '' ? Article::query()->where('name', $name)->first() : null;

        if ($existing || $name === '') {
            return $existing;
        }

        return Article::create([
            'name' => $name,
            'unit' => $unit ?? Unit::Kom,
            'tax_label' => $taxLabel,
            'gtin' => $gtin,
            'last_unit_price' => $this->pfening($item['unitPrice'] ?? 0),
            'is_active' => true,
        ]);
    }

    /** Kasa zna samo JIB kupca; klijent se traži po njemu, pa pravi sa JIB-om kao nazivom. */
    private function client(?string $buyerId): ?Client
    {
        $jib = trim(str_replace('VP:', '', (string) $buyerId));

        if ($jib === '') {
            return null;
        }

        return Client::query()->where('vat_id', $jib)->first()
            ?? Client::create(['name' => 'Kupac '.$jib, 'vat_id' => $jib, 'is_active' => true]);
    }

    /** Storno vežemo na original preko fiskalnog broja na koji se poziva. */
    private function linkRefund(Invoice $refund, ?string $referentNumber): void
    {
        if (! $referentNumber) {
            return;
        }

        $this->invoiceByFiscalNumber($referentNumber, $refund->id)?->update([
            'refund_invoice_id' => $refund->id,
        ]);
    }

    private function invoiceByFiscalNumber(string $fiscalNumber, ?int $exceptInvoiceId = null): ?Invoice
    {
        return FiscalRecord::query()
            ->with('invoice')
            ->whereHas('invoice')
            ->where('fiscal_invoice_number', $fiscalNumber)
            ->when($exceptInvoiceId, fn ($query) => $query->whereNot('invoice_id', $exceptInvoiceId))
            ->first()?->invoice;
    }

    /** Slika računa se traži u formatu iz podešavanja štampe. */
    private function imageQuery(): array
    {
        return [
            'receiptLayout' => $this->settings->receipt_layout,
            'imageFormat' => $this->settings->receipt_document_format,
            'includeHeaderAndFooter' => 'true',
        ];
    }

    private function storeReceipt(FiscalRecord $record, array $data, array $result): void
    {
        $image = $data['receiptImageBase64'] ?? null;
        $extension = strtolower((string) ($data['receiptImageFormat'] ?? $this->settings->receipt_document_format));

        if (is_string($image) && $image !== '') {
            $binary = base64_decode($image, true);

            if ($binary !== false && $binary !== '') {
                $this->receipts->store($record, $binary, $extension ?: 'png');

                return;
            }
        }

        $fallback = $this->receipts->extractFrom($result);

        if ($fallback !== null) {
            $this->receipts->store($record, $fallback['binary'], $fallback['extension']);
        }
    }

    private function paymentType(array $request): PaymentType
    {
        $type = (string) ((($request['payment'] ?? [])[0]['paymentType'] ?? '') ?: $this->settings->default_payment_type);

        return PaymentType::tryFrom($type) ?? PaymentType::Other;
    }

    /** Iznosi sa kase su u KM sa decimalama; u bazi su feninzi. */
    private function pfening(mixed $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }

    /**
     * CSV sa kase: broj, tip računa, tip transakcije, vrijeme fiskalizacije, iznos.
     *
     * @return array<int, array<string, mixed>>
     */
    private function parseCsv(string $body): array
    {
        $rows = [];

        foreach (preg_split('/\r\n|\r|\n/', trim($body)) ?: [] as $line) {
            $columns = str_getcsv(trim($line), ',', '"', '\\');

            if (count($columns) < 5 || ! str_contains((string) $columns[3], 'T')) {
                continue;
            }

            $rows[] = [
                'number' => (string) $columns[0],
                'type' => (string) $columns[1],
                'transaction_type' => (string) $columns[2],
                'issued_at' => (string) $columns[3],
                'total' => (float) $columns[4],
            ];
        }

        return $rows;
    }

    /**
     * @param  array<int, string>  $numbers
     * @return array<int, string>
     */
    private function importedNumbers(array $numbers): array
    {
        if ($numbers === []) {
            return [];
        }

        // `whereHas`: zapis bez računa (npr. nakon brisanja) ne smije lažno reći da je uvezen.
        return FiscalRecord::query()
            ->whereIn('fiscal_invoice_number', $numbers)
            ->whereHas('invoice')
            ->pluck('fiscal_invoice_number')
            ->all();
    }
}
