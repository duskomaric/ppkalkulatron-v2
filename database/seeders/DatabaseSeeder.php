<?php

namespace Database\Seeders;

use App\Enums\FiscalRecordType;
use App\Enums\InvoiceStatus;
use App\Models\Article;
use App\Models\Client;
use App\Models\Invoice;
use App\Services\FiscalRefundCreator;
use App\Services\InvoiceWriter;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Stope moraju postojati prije računa: bez njih upis stavke ne prolazi.
        $this->call(FiscalSeeder::class);

        $clients = collect([
            ['name' => 'PlusPlus IT d.o.o.', 'city' => 'Banja Luka', 'vat_id' => '4403927160006', 'tax_id' => '403927160006', 'email' => 'info@plusplusit.com'],
            ['name' => 'Mermer Gradnja d.o.o.', 'city' => 'Prnjavor', 'vat_id' => '4401234560001', 'tax_id' => '401234560001'],
            ['name' => 'Kafe Bar Centar', 'city' => 'Doboj', 'vat_id' => '4409876540002'],
        ])->map(fn (array $data) => Client::create($data + ['country' => 'BA']));

        $articles = collect([
            ['name' => 'Web razvoj', 'unit' => 'sat', 'tax_label' => 'F', 'last_unit_price' => 8000],
            ['name' => 'Održavanje sistema', 'unit' => 'usl', 'tax_label' => 'F', 'last_unit_price' => 25000],
            ['name' => 'Keramičke pločice', 'unit' => 'm2', 'tax_label' => 'F', 'last_unit_price' => 3550],
            ['name' => 'Konsultacije', 'unit' => 'sat', 'tax_label' => 'N', 'last_unit_price' => 12000],
        ])->map(fn (array $data) => Article::create($data));

        $writer = app(InvoiceWriter::class);

        $writer->create([
            'client_id' => $clients[0]->id,
            'payment_type' => 'WireTransfer',
            'currency' => 'BAM',
            'language' => 'sr_Latn',
            'date' => now()->subDays(12)->format('Y-m-d'),
            'due_date' => now()->subDays(12)->addDays(15)->format('Y-m-d'),
            'notes' => "Plaćanje na račun.\nHvala na saradnji.",
            'items' => [
                ['article_id' => $articles[0]->id, 'name' => 'Web razvoj', 'unit' => 'sat', 'tax_label' => 'F', 'quantity' => 24, 'unit_price' => '80.00'],
                ['article_id' => $articles[1]->id, 'name' => 'Održavanje sistema', 'unit' => 'usl', 'tax_label' => 'F', 'quantity' => 1, 'unit_price' => '250.00'],
            ],
        ]);

        $writer->create([
            'client_id' => $clients[1]->id,
            'payment_type' => 'Cash',
            'currency' => 'BAM',
            'language' => 'sr_Latn',
            'date' => now()->subDays(4)->format('Y-m-d'),
            'due_date' => now()->addDays(11)->format('Y-m-d'),
            'items' => [
                ['article_id' => $articles[2]->id, 'name' => 'Keramičke pločice', 'unit' => 'm2', 'tax_label' => 'F', 'quantity' => 42, 'unit_price' => '35.50'],
            ],
        ]);

        $writer->create([
            'client_id' => $clients[2]->id,
            'payment_type' => 'Card',
            'currency' => 'BAM',
            'language' => 'sr_Latn',
            'date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(15)->format('Y-m-d'),
            'items' => [
                ['article_id' => $articles[3]->id, 'name' => 'Konsultacije', 'unit' => 'sat', 'tax_label' => 'N', 'quantity' => 3, 'unit_price' => '120.00'],
            ],
        ]);

        $writer->create([
            'client_id' => null,
            'payment_type' => 'Cash',
            'currency' => 'BAM',
            'language' => 'sr_Latn',
            'date' => now()->format('Y-m-d'),
            'due_date' => now()->format('Y-m-d'),
            'notes' => 'Račun bez klijenta.',
            'items' => [
                ['article_id' => null, 'name' => 'Sitna usluga', 'unit' => 'kom', 'tax_label' => 'F', 'quantity' => 1, 'unit_price' => '15.00'],
            ],
        ]);

        $this->fiscalizedExamples();
    }

    /**
     * Fiskalizovani primjeri, da se svi statusi vide bez povezane kase.
     *
     * Fiskalni brojevi su izmišljeni — stvarni dolaze tek od uređaja, a ovdje služe
     * da račun ima zapis uz sebe (kopija, storno, provjera kod Poreske uprave).
     */
    private function fiscalizedExamples(): void
    {
        $invoices = Invoice::orderBy('id')->get();

        // Prvi račun je fiskalizovan i ostaje takav.
        $this->markFiscalized($invoices[0], 'TEST0001-TEST0001-1', FiscalRecordType::Original);

        // Drugi je fiskalizovan pa storniran: original ostaje fiskalizovan uz oznaku
        // da je poništen, a storno dokument nosi status storniranja.
        $this->markFiscalized($invoices[1], 'TEST0001-TEST0001-2', FiscalRecordType::Original);

        $refund = app(FiscalRefundCreator::class)->create($invoices[1]->fresh());
        $this->markFiscalized($refund, 'TEST0001-TEST0001-3', FiscalRecordType::Refund);
        $refund->update(['status' => InvoiceStatus::Refunded]);

        // Treći je uvezen sa kase — bez kupca, kakav uvoz i ume da bude.
        $invoices[2]->update(['imported_at' => now(), 'client_id' => null]);
        $this->markFiscalized($invoices[2], 'TEST0001-TEST0001-4', FiscalRecordType::Original);
    }

    private function markFiscalized(Invoice $invoice, string $fiscalNumber, FiscalRecordType $type): void
    {
        $invoice->update(['status' => InvoiceStatus::Fiscalized]);

        $invoice->fiscalRecords()->create([
            'type' => $type,
            'fiscal_invoice_number' => $fiscalNumber,
            'fiscal_counter' => '1/1ПП',
            'verification_url' => 'https://sandbox.suf.poreskaupravars.org/v/?vl=test',
            'fiscalized_at' => $invoice->date,
        ]);
    }
}
