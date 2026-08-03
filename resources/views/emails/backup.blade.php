<x-emails.layout title="Backup je spreman" :subtitle="config('app.name')">
    <p style="margin:0 0 16px;">U prilogu je backup iz aplikacije.</p>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#fffbeb;border:1px solid #fde68a;border-radius:8px;">
        <tr><td style="padding:16px;color:#78350f;">
            Sadrži {{ $invoiceCount }} PDF računa i {{ $fiscalDocumentCount }} fiskalnih dokumenata, uz manifest.csv za pregled sadržaja.
        </td></tr>
    </table>
</x-emails.layout>
