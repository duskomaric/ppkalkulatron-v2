<?php

namespace App\Services;

use App\Enums\FiscalRecordType;
use App\Enums\InvoiceStatus;
use App\Models\FiscalRecord;
use App\Models\Invoice;
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
        private FiscalPayloadFactory $payloads,
        private OFSService $ofs,
        private Diagnostics $diagnostics,
        private FiscalDeviceErrorMessage $errorMessages,
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

        $payload = $this->payloads->create(
            $invoice,
            $transactionType,
            $invoiceType,
            $referent,
            $this->resolveBuyerId($invoice),
        );

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
                'invoice_id' => $invoice->id,
                'request_id' => $record->request_id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException($this->errorMessages->forInvoice($response));
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
