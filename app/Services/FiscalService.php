<?php

namespace App\Services;

use App\Enums\FiscalRecordType;
use App\Enums\InvoiceStatus;
use App\Models\FiscalRecord;
use App\Models\Invoice;
use App\Models\TaxRate;
use App\Settings\FiscalSettings;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Fiskalizacija računa preko OFS ESIR-a. PHP na uređaju direktno poziva uređaj i
 * obrađuje njegov odgovor.
 */
class FiscalService
{
    /** JIB za veleprodajnog kupca koji svoj nema (strano lice). */
    public const FOREIGN_BUYER_ID = '9999999999999';

    public function __construct(
        private FiscalSettings $settings,
        private FiscalReceiptStore $receipts,
        private CurrencyConverter $converter,
        private OFSService $ofs,
        private Diagnostics $diagnostics,
    ) {}

    public function fiscalize(Invoice $invoice): FiscalRecord
    {
        if (in_array($invoice->status, [InvoiceStatus::Fiscalized, InvoiceStatus::Refunded, InvoiceStatus::RefundCreated], true)) {
            throw new RuntimeException('Račun nije moguće fiskalizovati.');
        }

        $record = $this->send($invoice, 'Sale', 'Normal', FiscalRecordType::Original, 'inv');

        $invoice->update(['status' => InvoiceStatus::Fiscalized]);

        return $record;
    }

    public function copy(Invoice $invoice): FiscalRecord
    {
        $original = $this->requireOriginal($invoice, 'Račun mora biti fiskalizovan prije štampe kopije.');

        return $this->send($invoice, 'Sale', 'Copy', FiscalRecordType::Copy, 'copy', $original);
    }

    /** Storno: isti sadržaj kao original, transactionType Refund uz referentna polja. */
    public function refund(Invoice $refundInvoice): FiscalRecord
    {
        $originalInvoice = $refundInvoice->originalInvoice;

        if (! $originalInvoice) {
            throw new RuntimeException('Ovaj račun nije storno nekog računa.');
        }

        if ($refundInvoice->status !== InvoiceStatus::RefundCreated) {
            // Bez ove brane ponovljen POST šalje uređaju drugu refundaciju.
            throw new RuntimeException('Ovaj storno je već fiskalizovan.');
        }

        $original = $this->requireOriginal($originalInvoice, 'Originalni račun mora biti fiskalizovan prije refundacije.');

        $record = $this->send($refundInvoice, 'Refund', 'Normal', FiscalRecordType::Refund, 'refund', $original);

        $refundInvoice->update(['status' => InvoiceStatus::Fiscalized]);
        $originalInvoice->update(['status' => InvoiceStatus::Refunded]);

        return $record;
    }

    private function requireOriginal(Invoice $invoice, string $message): FiscalRecord
    {
        $invoice->loadMissing('fiscalRecords');
        $original = $invoice->originalFiscalRecord();

        if (! $original?->fiscal_invoice_number) {
            throw new RuntimeException($message);
        }

        return $original;
    }

    private function send(
        Invoice $invoice,
        string $transactionType,
        string $invoiceType,
        FiscalRecordType $type,
        string $requestPrefix,
        ?FiscalRecord $referent = null,
    ): FiscalRecord {
        $invoice->loadMissing(['items.article', 'client']);

        if ($missing = $this->wholesaleBuyerMissing($invoice)) {
            throw new RuntimeException($missing);
        }

        $items = $this->items($invoice);
        $payload = $this->payload($invoice, $items, (float) array_sum(array_column($items, 'totalAmount')),
            $transactionType, $invoiceType, $referent);

        [$record, $isRetry] = $this->pendingRecord($invoice, $type, $requestPrefix);

        if ($isRetry) {
            $existing = $this->ofs->getInvoiceByRequestId($record->request_id);

            if (! $existing->successful()) {
                throw new RuntimeException('Nije moguće provjeriti prethodni zahtjev fiskalnom uređaju. Ne pokušavajte ponovo dok se veza ne vrati.');
            }

            $previousResponse = (array) $existing->json();

            if (isset($previousResponse['invoiceNumber'])) {
                return $this->completeRecord($record, $previousResponse);
            }
        }

        $this->diagnostics->debug('Fiskalizacija računa', ['invoice_id' => $invoice->id, 'request_id' => $record->request_id, 'type' => $type->value]);

        $response = $this->ofs->createInvoice($payload, $record->request_id);

        if (! $response->successful()) {
            $this->diagnostics->error('Fiskalizacija nije uspjela', [
                'invoice_id' => $invoice->id, 'status' => $response->status(), 'body' => $response->body(),
            ]);

            throw new RuntimeException('Uređaj je odbio račun (HTTP '.$response->status().'): '.Str::limit($response->body(), 200));
        }

        $data = (array) $response->json();

        if (! isset($data['invoiceNumber'])) {
            throw new RuntimeException('Neispravan odgovor fiskalnog uređaja.');
        }

        return $this->completeRecord($record, $data);
    }

    /**
     * RequestId se snima prije slanja. Kod izgubljenog odgovora isti zapis se
     * prvo traži na ESIR-u, čime se izbjegava izdavanje drugog fiskalnog računa.
     *
     * @return array{FiscalRecord, bool}
     */
    private function pendingRecord(Invoice $invoice, FiscalRecordType $type, string $requestPrefix): array
    {
        $pending = $invoice->fiscalRecords()
            ->where('type', $type->value)
            ->whereNull('fiscal_invoice_number')
            ->oldest('id')
            ->first();

        if ($pending) {
            return [$pending, true];
        }

        return [$invoice->fiscalRecords()->create([
            'type' => $type,
            'request_id' => self::requestId($requestPrefix, $invoice->id),
        ]), false];
    }

    private function completeRecord(FiscalRecord $record, array $data): FiscalRecord
    {
        if (! isset($data['invoiceNumber'])) {
            throw new RuntimeException('Neispravan odgovor fiskalnog uređaja.');
        }

        $receipt = $this->receipts->extractFrom($data);

        $record->update([
            'fiscal_invoice_number' => $data['invoiceNumber'],
            'fiscal_counter' => isset($data['invoiceCounter']) ? (string) $data['invoiceCounter'] : null,
            'verification_url' => $data['verificationUrl'] ?? null,
            'fiscalized_at' => now(),
        ]);

        if ($receipt !== null) {
            $this->receipts->store($record, $receipt['binary'], $receipt['extension']);
        }

        return $record;
    }

    /**
     * Po dokumentaciji OFS-a obavezni su name, gtin (8-14 znakova), labels, unitPrice,
     * quantity i totalAmount; iznosi su sa porezom, uređaj sam izvodi osnovicu.
     */
    private function items(Invoice $invoice): array
    {
        // Uređaju iznosi idu u KM, po kursu na datum računa.
        $toBam = fn (int $pfening) => $this->converter->toBam($pfening, $invoice->currency, $invoice->date);
        $zeroRateLabel = null;

        return $invoice->items->map(function ($item) use ($toBam, &$zeroRateLabel) {
            // GTIN je obavezan (8-14 znakova). Pravi barkod artikla ako ga ima,
            // inače id dopunjen nulama — uređaj traži nešto jedinstveno.
            $barcode = preg_replace('/\D/', '', (string) $item->article?->gtin);

            $gtin = strlen($barcode) >= 8
                ? substr($barcode, 0, 14)
                : substr(str_pad((string) ($item->article_id ?? $item->id), 8, '0', STR_PAD_LEFT), 0, 14);

            return [
                'name' => $item->name.' / '.$item->unit->value,
                'gtin' => $gtin,
                'quantity' => (float) $item->quantity,
                // Jedinična cijena se izvodi iz preračunatog ukupnog, da uređaj ne
                // odštampa red u kojem cijena × količina ne daje ukupno.
                'unitPrice' => abs($toBam($item->total)) / max(1, (int) $item->quantity) / 100,
                'totalAmount' => abs($toBam($item->total)) / 100,
                'labels' => [$item->tax_label ?: ($zeroRateLabel ??= $this->zeroRateLabel())],
            ];
        })->all();
    }

    /**
     * Oznaka sa nultom stopom, za stavke bez poreza.
     *
     * Pogađanje ovdje košta: 'A' je 9% PDV-a, pa bi uređaj naplatio porez koji na
     * računu ne stoji. Ako uređaj ne prijavljuje ni jednu nultu stopu, račun se ne
     * fiskalizuje — bolje jasno odbijanje nego pogrešan fiskalni račun.
     */
    private function zeroRateLabel(): string
    {
        $label = TaxRate::where('rate', 0)->orderBy('label')->value('label');

        if ($label === null) {
            throw new RuntimeException(
                'Stavka je bez poreske oznake, a uređaj ne prijavljuje nijednu nultu stopu. '.
                'Dodijelite poresku oznaku stavci prije fiskalizacije.'
            );
        }

        return $label;
    }

    private function payload(
        Invoice $invoice,
        array $items,
        float $total,
        string $transactionType,
        string $invoiceType,
        ?FiscalRecord $referent,
    ): array {
        $layout = $this->settings->receipt_layout;

        $payload = [
            'print' => $this->settings->print_receipt,
            'renderReceiptImage' => true,
            'receiptImageFormat' => $this->documentFormat($layout),
            'receiptLayout' => $layout,
            'receiptHeaderTextLines' => $this->settings->receipt_header_text_lines,
            'invoiceRequest' => [
                'invoiceType' => $invoiceType,
                'transactionType' => $transactionType,
                'payment' => [[
                    'amount' => $total,
                    'paymentType' => $invoice->payment_type?->value ?: $this->settings->default_payment_type,
                ]],
                'items' => $items,
                'cashier' => $this->settings->cashier ?: 'Prodavac',
            ],
        ];

        if ($referent?->fiscal_invoice_number) {
            $payload['invoiceRequest']['referentDocumentNumber'] = $referent->fiscal_invoice_number;
            $payload['invoiceRequest']['referentDocumentDT'] = $referent->fiscalized_at?->format('c');
        }

        if ($buyerId = $this->resolveBuyerId($invoice)) {
            $payload['invoiceRequest']['buyerId'] = $buyerId;
        }

        return $payload;
    }

    /**
     * Format koji izabrani raspored zaista može iscrtati.
     *
     * Podešavanja to već spriječavaju, ali ranije sačuvana kombinacija Invoice + Png
     * daje prazan račun od jednog piksela. Radije se vrati na format koji se iscrta
     * nego da fiskalizacija padne zbog izbora slike.
     */
    private function documentFormat(string $layout): string
    {
        $format = $this->settings->receipt_document_format;
        $allowed = $this->settings->allowedDocumentFormats();

        if (in_array($format, $allowed, true)) {
            return $format;
        }

        $this->diagnostics->error('Format fiskalnog dokumenta nije podržan za raspored', [
            'layout' => $layout, 'configured' => $format, 'used' => $allowed[0],
        ]);

        return $allowed[0];
    }

    /**
     * Identifikacija kupca po api.ofs.ba: JIB firme, broj lične karte ili pasoša.
     *
     * JIB klijenta je vat_id, ne tax_id — tamo stoji PDV broj. Evidentiranje
     * veleprodaje traži prefiks „VP:", a veleprodajni kupac bez svog JIB-a šalje se
     * kao VP:9999999999999. Bez ikakve identifikacije buyerId se izostavlja.
     */
    public function resolveBuyerId(Invoice $invoice): ?string
    {
        $jib = trim((string) $invoice->client?->vat_id);

        if (! $this->settings->wholesale) {
            return $jib !== '' ? $jib : null;
        }

        if ($jib !== '') {
            // Uređaj koji radi samo veleprodaju sam dodaje prefiks; dva puta bi bilo pogrešno.
            return str_starts_with($jib, 'VP:') ? $jib : 'VP:'.$jib;
        }

        return $this->buyerIsForeign($invoice) ? 'VP:'.self::FOREIGN_BUYER_ID : null;
    }

    /** Veleprodajni promet mora imati kupca — reci šta nedostaje umjesto da uređaj odbije račun. */
    public function wholesaleBuyerMissing(Invoice $invoice): ?string
    {
        if (! $this->settings->wholesale || $this->resolveBuyerId($invoice) !== null) {
            return null;
        }

        return $invoice->client
            ? 'Za veleprodaju je obavezan JIB kupca. Unesite JIB u podatke klijenta, ili isključite veleprodaju u fiskalnim podešavanjima.'
            : 'Za veleprodaju je obavezan kupac sa JIB-om. Račun bez klijenta se ne može evidentirati kao veleprodaja.';
    }

    /** Država je slobodan tekst — sve što nije prepoznato kao BiH je strano, prazno je domaće. */
    private function buyerIsForeign(Invoice $invoice): bool
    {
        $country = trim((string) $invoice->client?->country);

        if ($country === '') {
            return false;
        }

        return ! in_array(mb_strtolower($country), [
            'ba', 'bih', 'bh', 'bosna i hercegovina', 'bosna and herzegovina',
            'bosnia and herzegovina', 'bosnia', 'босна и херцеговина',
        ], true);
    }

    /**
     * RequestId omogućava da se izgubljen odgovor kasnije pronađe, a OFS traži najviše
     * 32 alfanumerička znaka — dakle bez razdvajača.
     */
    public static function requestId(string $prefix, int $invoiceId): string
    {
        return substr($prefix.$invoiceId.Str::random(8), 0, 32);
    }
}
