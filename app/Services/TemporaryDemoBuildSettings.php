<?php

namespace App\Services;

use App\Settings\BackupSettings;
use App\Settings\CompanySettings;
use App\Settings\DocumentSettings;
use App\Settings\FiscalSettings;
use App\Settings\MailSettings;
use App\Settings\SecuritySettings;
use Illuminate\Support\Facades\Hash;

/**
 * Privremena početna konfiguracija za interne testne Android buildove.
 *
 * Uklanja se zajedno sa pratećom settings migracijom prije javne distribucije.
 */
class TemporaryDemoBuildSettings
{
    public function __construct(
        private readonly BackupSettings $backupSettings,
        private readonly CompanySettings $companySettings,
        private readonly DocumentSettings $documentSettings,
        private readonly FiscalSettings $fiscalSettings,
        private readonly MailSettings $mailSettings,
        private readonly SecuritySettings $securitySettings,
    ) {}

    /**
     * Popunjava samo potpuno novu aplikaciju. Nikad ne prepisuje korisničke postavke.
     */
    public function seedIfPristine(): bool
    {
        if (! $this->isPristine()) {
            return false;
        }

        $this->companySettings->fill([
            'name' => 'Throwcode sp Dusko Maric',
            'address' => 'Jefimijina 53',
            'city' => 'Prnjavor',
            'zip' => '78430',
            'country' => 'BA',
            'phone' => '065921424',
            'email' => 'dusko.maric@plusplusit.com',
            'identification_number' => '1334566770592',
            'vat_number' => null,
            'is_vat_obligor' => false,
            'is_small_entrepreneur' => true,
            'small_entrepreneur_note' => 'Mali preduzetnik — nije u sistemu PDV-a.',
        ])->save();

        $this->documentSettings->fill([
            'template' => 'classic',
            'language' => 'sr_Latn',
            'invoice_due_days' => 15,
            'invoice_notes' => "Throwcode sp Dusko Maric nije u sistemu PDV-a.\nOva faktura je validna bez pečata i potpisa.",
        ])->save();

        $this->fiscalSettings->fill([
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
        ])->save();

        $this->mailSettings->fill([
            'from_address' => 'duskomaric86@gmail.com',
            'from_name' => 'Duško Marić',
            'host' => 'smtp.gmail.com',
            'port' => 587,
            'username' => 'duskomaric86@gmail.com',
            'password' => 'mczszzwgqpxebnjg',
            'encryption' => 'tls',
        ])->save();

        $this->backupSettings->fill([
            'email' => 'duskomaric86@gmail.com',
        ])->save();

        $this->securitySettings->fill([
            'pin_hash' => Hash::make('1111'),
            'auto_lock_minutes' => 5,
        ])->save();

        return true;
    }

    private function isPristine(): bool
    {
        return blank($this->companySettings->name)
            && blank($this->backupSettings->email)
            && blank($this->fiscalSettings->api_key)
            && blank($this->mailSettings->host)
            && blank($this->securitySettings->pin_hash);
    }
}
