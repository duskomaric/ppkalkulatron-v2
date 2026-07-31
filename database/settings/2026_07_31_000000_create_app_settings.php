<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('company.name', '');
        $this->migrator->add('company.address', null);
        $this->migrator->add('company.city', null);
        $this->migrator->add('company.zip', null);
        $this->migrator->add('company.country', 'BA');
        $this->migrator->add('company.phone', null);
        $this->migrator->add('company.email', null);
        $this->migrator->add('company.identification_number', null);
        $this->migrator->add('company.vat_number', null);
        $this->migrator->add('company.is_vat_obligor', true);

        $this->migrator->add('fiscal.base_url', 'https://pos.ofs.ba');
        $this->migrator->add('fiscal.api_key', null);
        $this->migrator->add('fiscal.serial_number', null);
        $this->migrator->add('fiscal.pac', null);
        $this->migrator->add('fiscal.device_mode', 'cloud');
        $this->migrator->add('fiscal.wholesale', false);
        $this->migrator->add('fiscal.receipt_layout', 'Slip');
        $this->migrator->add('fiscal.receipt_image_format', 'Png');
        $this->migrator->add('fiscal.render_receipt_image', true);
        $this->migrator->add('fiscal.print_receipt', false);
        $this->migrator->add('fiscal.receipt_header_text_lines', []);
        $this->migrator->add('fiscal.default_payment_type', 'Cash');

        $this->migrator->add('numbering.reset_yearly', true);
        $this->migrator->add('numbering.pad_zeros', 4);
        $this->migrator->add('numbering.invoice_prefix', '');
        $this->migrator->add('numbering.invoice_starting_number', 1);
        $this->migrator->add('numbering.proforma_prefix', '');
        $this->migrator->add('numbering.proforma_starting_number', 1);
        $this->migrator->add('numbering.quote_prefix', '');
        $this->migrator->add('numbering.quote_starting_number', 1);

        $this->migrator->add('mail.from_address', null);
        $this->migrator->add('mail.from_name', null);
        $this->migrator->add('mail.host', null);
        $this->migrator->add('mail.port', null);
        $this->migrator->add('mail.username', null);
        $this->migrator->add('mail.password', null);
        $this->migrator->add('mail.encryption', null);

        $this->migrator->add('document.template', 'classic');
        $this->migrator->add('document.invoice_due_days', 15);
        $this->migrator->add('document.proforma_due_days', 15);
        $this->migrator->add('document.quote_valid_days', 30);
        $this->migrator->add('document.invoice_notes', null);
        $this->migrator->add('document.proforma_notes', null);
        $this->migrator->add('document.quote_notes', null);

        $this->migrator->add('security.pin_hash', null);
    }
};
