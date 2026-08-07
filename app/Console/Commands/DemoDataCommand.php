<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\BankAccount;
use App\Models\Client;
use App\Models\FiscalTaxRate;
use App\Settings\BackupSettings;
use App\Settings\CompanySettings;
use App\Settings\DocumentSettings;
use App\Settings\FiscalSettings;
use App\Settings\MailSettings;
use App\Settings\SecuritySettings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

/**
 * PRIVREMENO: demo podaci za interno testiranje.
 *
 * Svi podaci stoje ovdje, na jednom mjestu — ranije su bili razasuti po seederima
 * i po jednoj settings migraciji, pa su se vraćali i poslije reseta aplikacije.
 * Popunjava sve korake početnog podešavanja, uključujući poreske stope sandbox
 * uređaja — bez njih se ne može dodati ni artikal ni račun.
 *
 * Uklanja se prije javne distribucije, zajedno sa dugmetom u vodiču i rutom.
 */
class DemoDataCommand extends Command
{
    protected $signature = 'app:demo-data';

    protected $description = 'Popuni praznu aplikaciju demo podacima za testiranje';

    /** Sandbox uređaj OFS-a; na njemu fiskalizacija radi bez prave kase. */
    private const DEVICE = [
        'base_url' => 'https://pos.ofs.ba',
        'api_key' => 'bb7584a167578b89c459d6ab1759b0cc',
        'serial_number' => 'F41AEFFF110A4B5ABB266299A41EE479',
        'pac' => '123456',
        'device_mode' => 'cloud',
        'wholesale' => false,
        'receipt_layout' => 'Slip',
        'receipt_document_format' => 'Png',
        'print_receipt' => false,
        'receipt_header_text_lines' => [],
        'default_payment_type' => 'WireTransfer',
        'cashier' => 'Prodavac',
    ];

    /**
     * Stope sandbox uređaja: oznaka, procenat, kategorija i tip kategorije.
     *
     * Prvo preuzimanje sa stvarne kase ih zamijeni njenima.
     *
     * @var list<array{0: string, 1: int, 2: string, 3: int}>
     */
    private const TAX_RATES = [
        ['A', 9, 'VAT', 0],
        ['B', 0, 'VAT', 0],
        ['C', 0, 'VAT-EXCL', 0],
        ['E', 6, 'STT', 0],
        ['F', 11, 'ECAL', 0],
        ['N', 0, 'N-TAX', 0],
        ['P', 40, 'PBL', 2],
        ['T', 2, 'TOTL', 1],
    ];

    private const COMPANY = [
        'name' => 'Kodeks Studio d.o.o. Banja Luka',
        'address' => 'Kralja Petra I Karađorđevića 87',
        'city' => 'Banja Luka',
        'zip' => '78000',
        'country' => 'BA',
        'phone' => '+387 65 412 780',
        'email' => 'racuni@kodeks-studio.ba',
        'identification_number' => '4404567890123',
        'vat_number' => null,
        'is_vat_obligor' => false,
        'is_small_entrepreneur' => true,
    ];

    private const BANK_ACCOUNT = [
        'bank_name' => 'Nova banka a.d. Banja Luka',
        'account_number' => '5550070123456789',
        'swift' => 'NOBIBA22',
        'show_on_documents' => true,
    ];

    /** @var list<array<string, mixed>> */
    private const CLIENTS = [
        [
            'name' => 'Vidik Media d.o.o.',
            'address' => 'Veselina Masleše 12',
            'city' => 'Banja Luka',
            'zip' => '78000',
            'vat_id' => '4401112223334',
            'tax_id' => '401112223334',
            'email' => 'racunovodstvo@vidikmedia.ba',
            'phone' => '+387 51 214 330',
        ],
        [
            'name' => 'Stolarija Jović s.p.',
            'address' => 'Karađorđeva 45',
            'city' => 'Laktaši',
            'zip' => '78250',
            'vat_id' => '4405556667778',
            'email' => 'stolarija.jovic@gmail.com',
            'phone' => '+387 65 880 214',
        ],
        [
            'name' => 'Apoteka Zdravlje d.o.o.',
            'address' => 'Kralja Petra I 3',
            'city' => 'Prijedor',
            'zip' => '79101',
            'vat_id' => '4409998887776',
            'tax_id' => '409998887776',
            'email' => 'nabavka@apoteka-zdravlje.ba',
        ],
    ];

    /**
     * Usluge razvoja softvera; oznaka „F" je stopa sandbox uređaja.
     *
     * Cijene su u fenizima i sa porezom, kako ih aplikacija svuda i drži.
     *
     * @var list<array<string, mixed>>
     */
    private const ARTICLES = [
        ['name' => 'Izrada web aplikacije', 'description' => 'Razvoj po satu, uz sedmični pregled urađenog.', 'unit' => 'sat', 'tax_label' => 'F', 'last_unit_price' => 9000],
        ['name' => 'Održavanje i podrška', 'description' => 'Mjesečni paket: nadzor, sigurnosne zakrpe i sitne izmjene.', 'unit' => 'usl', 'tax_label' => 'F', 'last_unit_price' => 35000],
        ['name' => 'Konsultacije i analiza', 'description' => 'Razgovor o rješenju, procjena obima i plan rada.', 'unit' => 'sat', 'tax_label' => 'F', 'last_unit_price' => 12000],
        ['name' => 'Postavljanje na server', 'description' => 'Priprema servera, objava aplikacije i praćenje prvog dana.', 'unit' => 'usl', 'tax_label' => 'F', 'last_unit_price' => 25000],
    ];

    public function handle(
        CompanySettings $company,
        DocumentSettings $documents,
        FiscalSettings $fiscal,
        MailSettings $mail,
        BackupSettings $backup,
        SecuritySettings $security,
    ): int {
        if (! $this->isPristine($company, $fiscal, $mail, $backup, $security)) {
            $this->components->error('Demo podaci se upisuju samo u praznu aplikaciju.');

            return self::FAILURE;
        }

        $company->fill(self::COMPANY)->save();
        $fiscal->fill(self::DEVICE)->save();

        $documents->fill([
            'template' => 'classic',
            'language' => 'sr_Latn',
            'invoice_due_days' => 15,
            'invoice_notes' => self::COMPANY['name']." nije u sistemu PDV-a.\nOva faktura je validna bez pečata i potpisa.",
        ])->save();

        $mail->fill([
            'from_address' => 'duskomaric86@gmail.com',
            'from_name' => 'Duško Marić',
            'host' => 'smtp.gmail.com',
            'port' => 587,
            'username' => 'duskomaric86@gmail.com',
            'password' => 'mczszzwgqpxebnjg',
            'encryption' => 'tls',
        ])->save();

        $backup->fill(['email' => 'duskomaric86@gmail.com'])->save();
        $security->fill(['pin_hash' => Hash::make('1111'), 'auto_lock_minutes' => 5])->save();

        // Stope se dopunjavaju, ne dupliraju: oznaka je jedinstvena.
        foreach (self::TAX_RATES as [$label, $rate, $category, $categoryType]) {
            FiscalTaxRate::updateOrCreate(['label' => $label], [
                'rate' => $rate,
                'category_name' => $category,
                'category_type' => $categoryType,
            ]);
        }

        BankAccount::create(self::BANK_ACCOUNT);

        foreach (self::CLIENTS as $client) {
            Client::create($client + ['country' => 'BA', 'is_active' => true]);
        }

        foreach (self::ARTICLES as $article) {
            Article::create($article + ['is_active' => true]);
        }

        $this->components->info('Demo podaci su upisani.');
        $this->components->twoColumnDetail('firma', self::COMPANY['name']);
        $this->components->twoColumnDetail('kasa', self::DEVICE['base_url'].' (testna)');
        $this->components->twoColumnDetail('klijenti', (string) count(self::CLIENTS));
        $this->components->twoColumnDetail('artikli', (string) count(self::ARTICLES));
        $this->components->twoColumnDetail('poreske stope', count(self::TAX_RATES).' (sa testne kase)');

        return self::SUCCESS;
    }

    /** Nikad ne prepisuje zatečeno stanje: prvo reset, pa demo podaci. */
    private function isPristine(
        CompanySettings $company,
        FiscalSettings $fiscal,
        MailSettings $mail,
        BackupSettings $backup,
        SecuritySettings $security,
    ): bool {
        return blank($company->name)
            && blank($fiscal->api_key)
            && blank($mail->host)
            && blank($backup->email)
            && blank($security->pin_hash)
            && ! Client::query()->exists()
            && ! Article::query()->exists()
            && ! BankAccount::query()->exists();
    }
}
