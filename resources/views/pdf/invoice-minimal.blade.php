@php
    $formatAmount = fn ($pfening) => number_format($pfening / 100, 2, ',', '.');
    $currency = $invoice->currency ?: 'BAM';
    $showVat = $company->is_vat_obligor ?? true;
@endphp
<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="utf-8">
    <title>Faktura {{ $invoice->invoice_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: DejaVu Sans, sans-serif; }
        body { font-size: 8.5pt; color: #000; line-height: 1.3; background: #fff; }
        .page { padding: 25px 25px 60px 25px; max-width: 210mm; margin: 0 auto; position: relative; min-height: 100vh; }

        /* Header Table */
        .header-table { width: 100%; margin-bottom: 18px; padding-bottom: 10px; border-bottom: 1px solid #000; border-collapse: collapse; }
        .header-table td { vertical-align: middle; border: none; }
        .header-left { width: 50%; }
        .header-right { width: 50%; text-align: right; }

        .company-name-left { font-size: 16pt; font-weight: 700; letter-spacing: -0.3px; }
        .company-info { font-size: 7.5pt; line-height: 1.35; }
        .company-name { font-size: 12pt; font-weight: 700; margin-bottom: 3px; }

        /* Invoice Title */
        .invoice-title-section {
            margin-bottom: 16px;
            border-bottom: 3px solid #000;
            padding: 4px 0 10px 0;
        }
        .invoice-label {
            font-size: 18pt;
            font-weight: 700;
        }
        .invoice-number {
            font-size: 9pt;
            margin-left: 8px;
        }

        /* Info Section Table */
        .info-table { width: 100%; margin-bottom: 18px; border-collapse: collapse; }
        .info-table td { vertical-align: top; border: none; }
        .info-left { width: 47%; padding-right: 3%; }
        .info-right { width: 47%; padding-left: 3%; }

        .info-label { font-size: 6.5pt; text-transform: uppercase; font-weight: 700; border-bottom: 1px solid #000; margin-bottom: 5px; padding-bottom: 2px; }
        .info-content { font-size: 8pt; line-height: 1.3; }
        .info-name { font-weight: 700; font-size: 8.5pt; margin-bottom: 2px; }

        /* Detail Rows */
        .detail-table { width: 100%; border-collapse: collapse; }
        .detail-table td { border: none; padding: 1px 0; font-size: 7.5pt; }
        .detail-label { width: 50%; }
        .detail-value { width: 50%; text-align: right; font-weight: 700; }

        /* Items Table */
        .items-table { width: 100%; border-collapse: collapse; margin: 10px 0 8px 0; border-bottom: 2px solid #000; }
        .items-table th {
            padding: 5px 3px;
            text-align: left;
            font-size: 6.5pt;
            text-transform: uppercase;
            font-weight: 700;
            border-bottom: 2px solid #000;
        }
        .items-table td {
            padding: 3px;
            border-bottom: 1px solid #000;
            font-size: 8pt;
            vertical-align: top;
        }
        .items-table tbody tr:last-child td { border-bottom: none; }

        .text-right { text-align: right; }
        .items-table th.text-right,
        .items-table td.text-right { text-align: right; }
        .items-table th.text-center,
        .items-table td.text-center { text-align: center; }
        .item-name { font-weight: 700; }
        .item-desc { font-size: 7pt; margin-top: 1px; }

        /* Totals Table */
        .totals-wrapper { width: 100%; margin-top: 8px; border-collapse: collapse; }
        .totals-wrapper td { border: none; vertical-align: top; }
        .totals-spacer { width: 58%; }
        .totals-content { width: 42%; }

        .totals-table { width: 100%; border-collapse: collapse; }
        .totals-table td { padding: 4px 0; font-size: 8pt; border: none; }
        .totals-value { text-align: right; font-weight: 700; }
        .totals-table .total-row td {
            padding-top: 6px;
            border-top: 2px solid #000;
            font-size: 10pt;
            font-weight: 700;
        }

        /* Amount in words */
        .amount-in-words { margin-top: 8px; text-align: right; font-size: 7pt; font-style: italic; color: #555; }

        /* Notes */
        .notes-section {
            margin-top: 10px;
            padding: 6px 8px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }
        .notes-label {
            font-size: 6.5pt;
            text-transform: uppercase;
            font-weight: 700;
            margin-bottom: 3px;
        }
        .notes-text { font-size: 7.5pt; }

        /* Signature Section */
        .signature-section {
            margin-top: 15px;
            width: 100%;
            border-collapse: collapse;
        }
        .signature-section td {
            border: none;
            vertical-align: bottom;
            padding: 0 10px;
            text-align: center;
        }
        .signature-left { width: 50%; }
        .signature-right { width: 50%; }

        .signature-mp { font-size: 7pt; color: #666; margin-bottom: 24px; letter-spacing: 0.6px; }
        .signature-line {
            border-bottom: 1px solid #000;
            width: 180px;
            margin: 0 auto 5px auto;
        }
        .signature-label {
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.6px;
            font-size: 7pt;
        }

        /* Footer */
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 8px 0;
            border-top: 1px solid #000;
            text-align: center;
            font-size: 6.5pt;
            background: #fff;
        }

        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .page { padding: 10mm 10mm 20mm 10mm; }
            .footer { position: fixed; bottom: 0; }
        }
    </style>
</head>
<body>
<div class="page">

    <table class="header-table">
        <tr>
            <td class="header-left">
                <div class="company-name-left">{{ $company->name }}</div>
            </td>
            <td class="header-right">
                <div class="company-info">
                    {{ $company->name }}<br>
                    @if($company->address){{ $company->address }}<br>@endif
                    @if($company->zip || $company->city)
                        {{ $company->zip }} {{ $company->city }}@if($company->country), {{ $company->country }}@endif<br>
                    @endif
                    @if($company->identification_number)JIB: {{ $company->identification_number }}<br>@endif
                    @if($company->vat_number)PDV: {{ $company->vat_number }}@endif
                    @if(isset($bankAccounts) && $bankAccounts->isNotEmpty())
                        <br><br>
                        <strong>Instrukcije za plaćanje:</strong><br>
                        @foreach($bankAccounts as $ba)
                            {{ $ba->bank_name }}: {{ $ba->account_number }}@if($ba->swift) | SWIFT: {{ $ba->swift }}@endif
                            @if(! $loop->last)<br>@endif
                        @endforeach
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <table class="info-table">
        <tr>
            <td class="info-left">
                <div class="info-label">Kupac</div>
                <div class="info-content">
                    <div class="info-name">{{ $invoice->client?->name }}</div>
                    @if($invoice->client?->address){{ $invoice->client->address }}<br>@endif
                    @if($invoice->client?->zip || $invoice->client?->city)
                        {{ $invoice->client->zip }} {{ $invoice->client->city }}<br>
                    @endif
                    @if($invoice->client?->country){{ $invoice->client->country }}<br>@endif
                    @if($invoice->client?->phone){{ $invoice->client->phone }}<br>@endif
                    @if($invoice->client?->vat_id)JIB: {{ $invoice->client->vat_id }}<br>@endif
                    @if($invoice->client?->tax_id)PDV: {{ $invoice->client->tax_id }}@endif
                </div>
            </td>
            <td class="info-right">
                <div class="info-label">Detalji</div>
                <div class="info-content">
                    <table class="detail-table">
                        <tr>
                            <td class="detail-label">Rok dospijeća</td>
                            <td class="detail-value">{{ $invoice->due_date?->format('d.m.Y.') ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="detail-label">Datum izdavanja</td>
                            <td class="detail-value">{{ $invoice->date?->format('d.m.Y.') ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="detail-label">Način plaćanja</td>
                            <td class="detail-value">{{ $invoice->payment_type?->label() ?? '-' }}</td>
                        </tr>
                        @php
                            $originalFiscal = $invoice->fiscalRecords->firstWhere('type', \App\Enums\FiscalRecordType::Original);
                        @endphp
                        @if($originalFiscal?->fiscal_invoice_number)
                            <tr>
                                <td class="detail-label">Br. fiskalnog računa</td>
                                <td class="detail-value">{{ $originalFiscal->fiscal_invoice_number }}</td>
                            </tr>
                        @endif
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <div class="invoice-title-section">
        <span class="invoice-label">Račun</span>
        <span class="invoice-number">{{ $invoice->invoice_number }}</span>
    </div>

    <table class="items-table">
        <thead>
        <tr>
            <th style="width:45%">Opis</th>
            <th class="text-center" style="width:10%">Kol.</th>
            <th class="text-right" style="width:15%">Cijena</th>
            @if($showVat)<th class="text-center" style="width:10%">PDV</th>@endif
            <th class="text-right" style="width:20%">Ukupno</th>
        </tr>
        </thead>
        <tbody>
        @foreach($invoice->items as $item)
            <tr>
                <td>
                    <div class="item-name">{{ $item->name }}</div>
                    @if($item->description)
                        <div class="item-desc">{!! nl2br(e($item->description)) !!}</div>
                    @endif
                </td>
                <td class="text-center">{{ $item->quantity }}</td>
                <td class="text-right">
                    {{ $formatAmount($item->quantity > 0 ? $item->total / $item->quantity : 0) }} {{ $currency }}
                </td>
                @if($showVat)<td class="text-center">{{ $item->tax_rate / 100 }}%</td>@endif
                <td class="text-right" style="font-weight: 700;">
                    {{ $formatAmount($item->total) }} {{ $currency }}
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <table class="totals-wrapper">
        <tr>
            <td class="totals-spacer"></td>
            <td class="totals-content">
                <table class="totals-table">
                    <tr>
                        <td>Osnovica</td>
                        <td class="totals-value">{{ $formatAmount($invoice->subtotal) }} {{ $currency }}</td>
                    </tr>
                    @if($showVat)
                    <tr>
                        <td>PDV</td>
                        <td class="totals-value">{{ $formatAmount($invoice->tax_total) }} {{ $currency }}</td>
                    </tr>
                    @endif
                    <tr class="total-row">
                        <td>UKUPNO</td>
                        <td class="totals-value">{{ $formatAmount($invoice->total) }} {{ $currency }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="amount-in-words">
        Slovima:
        @php
            $locale = $invoice->language?->value ?? 'sr_Latn_BA';
            $spelled = null;
            if (class_exists(\NumberFormatter::class)) {
                try {
                    $spelled = (new \NumberFormatter($locale, \NumberFormatter::SPELLOUT))->format(intdiv($invoice->total, 100));
                } catch (\Throwable $e) {
                    $spelled = null;
                }
            }
        @endphp
        {{ $spelled ?? number_format(intdiv($invoice->total, 100), 0, ',', '.') }}
        {{ $currency }}
        i {{ str_pad($invoice->total % 100, 2, '0', STR_PAD_LEFT) }}/100
    </div>

    @if($invoice->notes)
        <div class="notes-section">
            <div class="notes-label">Napomena</div>
            <div class="notes-text">{!! nl2br(e($invoice->notes)) !!}</div>
        </div>
    @endif

    <table class="signature-section">
        <tr>
            <td class="signature-left">
                <div class="signature-mp">M.P.</div>
                <div class="signature-line"></div>
                <div class="signature-label">Izdao</div>
            </td>
            <td class="signature-right">
                <div class="signature-mp">M.P.</div>
                <div class="signature-line"></div>
                <div class="signature-label">Primio</div>
            </td>
        </tr>
    </table>

    <div class="footer">
        {{ $company->name }}
    </div>

</div>
</body>
</html>
