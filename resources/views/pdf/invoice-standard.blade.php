@php
    $formatAmount = fn ($pfening) => number_format($pfening / 100, 2, ',', '.');
    $currency = $invoice->currency ?: 'BAM';
    $showVat = $company->is_vat_obligor ?? true;
    $smallNote = ($company->is_small_entrepreneur ?? false) ? trim((string) $company->small_entrepreneur_note) : '';
@endphp
<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="utf-8">
    <title>Faktura {{ $invoice->invoice_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: DejaVu Sans, sans-serif; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 8pt;
            color: #000;
            line-height: 1.3;
            background: #fff;
        }
        .page {
            padding: 20px 20px 55px 20px;
            max-width: 210mm;
            margin: 0 auto;
            position: relative;
            min-height: 100vh;
        }

        /* Header Table */
        .header-table {
            width: 100%;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 2px solid #000;
            border-collapse: collapse;
        }

        .header-table td { border: none; vertical-align: middle; }
        .header-left { width: 50%; }
        .header-right { width: 50%; text-align: right; }

        .company-name-left {
            font-size: 16pt;
            font-weight: 700;
            letter-spacing: -0.3px;
        }

        .company-name {
            font-size: 12pt;
            font-weight: 700;
            margin-bottom: 3px;
        }

        .company-info {
            font-size: 7pt;
            line-height: 1.3;
        }

        /* Invoice Title Table */
        .invoice-title-table {
            width: 100%;
            margin-bottom: 14px;
            padding: 10px 0;
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
            border-collapse: collapse;
        }

        .invoice-title-table td { border: none; vertical-align: middle; }
        .title-left { width: 50%; }
        .title-right { width: 50%; text-align: right; font-size: 7pt; line-height: 1.3; }

        .invoice-label {
            font-size: 16pt;
            font-weight: 700;
        }

        .invoice-number {
            font-size: 9pt;
            font-weight: 700;
            margin-left: 6px;
        }

        /* Info Section Table */
        .info-table {
            width: 100%;
            margin-bottom: 16px;
            border-collapse: collapse;
        }

        .info-table td { border: none; vertical-align: top; }
        .info-left { width: 48%; padding-right: 2%; }
        .info-right { width: 48%; padding-left: 2%; }

        .info-label {
            font-size: 6.5pt;
            text-transform: uppercase;
            font-weight: 700;
            margin-bottom: 3px;
            padding-bottom: 2px;
            border-bottom: 1px solid #000;
        }

        .info-name {
            font-weight: 700;
            font-size: 8pt;
            margin-bottom: 2px;
        }

        .info-content {
            font-size: 7.5pt;
            line-height: 1.3;
        }

        /* Detail Table */
        .detail-table { width: 100%; border-collapse: collapse; }
        .detail-table td { border: none; padding: 1px 0; font-size: 7pt; }
        .detail-label { width: 50%; }
        .detail-value { width: 50%; text-align: right; font-weight: 700; }

        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 6px 0;
        }

        .items-table th {
            padding: 5px 3px;
            text-align: left;
            font-size: 6.5pt;
            text-transform: uppercase;
            font-weight: 700;
            background: #000;
            color: #fff;
            border: 1px solid #000;
        }

        .items-table td {
            padding: 4px 3px;
            font-size: 7.5pt;
            vertical-align: top;
            border: 1px solid #000;
            font-family: DejaVu Sans, sans-serif;
        }

        .text-right { text-align: right; font-family: DejaVu Sans, sans-serif; }
        .items-table th.text-right,
        .items-table td.text-right { text-align: right; }
        .items-table th.text-center,
        .items-table td.text-center { text-align: center; }

        .item-name {
            font-weight: 700;
            line-height: 1.2;
            font-family: DejaVu Sans, sans-serif;
        }

        .item-desc {
            font-size: 6.5pt;
            margin-top: 1px;
            line-height: 1.2;
            font-family: DejaVu Sans, sans-serif;
        }

        /* Totals Section Table */
        .totals-wrapper {
            width: 100%;
            margin-top: 6px;
            border-collapse: collapse;
        }

        .totals-wrapper td { border: none; vertical-align: top; }
        .totals-left { width: 50%; }
        .totals-right { width: 50%; }

        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }

        .totals-table td {
            padding: 3px 0;
            font-size: 7.5pt;
            border: none;
            font-family: DejaVu Sans, sans-serif;
        }

        .totals-value {
            text-align: right;
            font-weight: 700;
            font-family: DejaVu Sans, sans-serif;
        }

        .totals-table .total-row td {
            padding-top: 5px;
            border-top: 2px solid #000;
            font-size: 9.5pt;
            font-weight: 700;
        }

        /* Amount in words */
        .amount-in-words { margin-top: 8px; text-align: right; font-size: 7pt; font-style: italic; color: #555; }

        /* Notes Section */
        .notes-section {
            margin-top: 8px;
            padding: 6px;
            border: 1px solid #000;
        }

        .notes-label {
            font-size: 6.5pt;
            text-transform: uppercase;
            font-weight: 700;
            margin-bottom: 3px;
        }

        .notes-text {
            font-size: 7pt;
            line-height: 1.3;
        }

        /* Signature Section */
        .signature-section {
            margin-top: 12px;
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

        .signature-mp { font-size: 7pt; color: #666; margin-bottom: 22px; letter-spacing: 0.6px; }
        .signature-line {
            border-bottom: 1px solid #000;
            width: 175px;
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
            padding: 6px 0;
            border-top: 1px solid #000;
            text-align: center;
            font-size: 6.5pt;
            background: #fff;
            font-family: DejaVu Sans, sans-serif;
        }

        /* Print optimizacija */
        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .page {
                padding: 10mm 10mm 20mm 10mm;
            }
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
                <div class="info-label">Detalji plaćanja</div>
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

    <table class="invoice-title-table">
        <tr>
            <td class="title-left">
                <span class="invoice-label">Račun</span>
                <span class="invoice-number">{{ $invoice->invoice_number }}</span>
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
        <tr>
            <th style="width:45%">Opis</th>
            <th class="text-center" style="width:10%">Količina</th>
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
                <td class="text-center" style="font-family: DejaVu Sans, sans-serif;">{{ $item->quantity }}</td>
                <td class="text-right" style="font-family: DejaVu Sans, sans-serif;">
                    {{ $formatAmount($item->quantity > 0 ? $item->total / $item->quantity : 0) }} {{ $currency }}
                </td>
                @if($showVat)<td class="text-center" style="font-family: DejaVu Sans, sans-serif;">{{ $item->tax_rate / 100 }}%</td>@endif
                <td class="text-right" style="font-family: DejaVu Sans, sans-serif;">
                    {{ $formatAmount($item->total) }} {{ $currency }}
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <table class="totals-wrapper">
        <tr>
            <td class="totals-left"></td>
            <td class="totals-right">
                <table class="totals-table">
                    <tr>
                        <td style="font-family: DejaVu Sans, sans-serif;">Osnovica</td>
                        <td class="totals-value" style="font-family: DejaVu Sans, sans-serif;">{{ $formatAmount($invoice->subtotal) }} {{ $currency }}</td>
                    </tr>
                    @if($showVat)
                    <tr>
                        <td style="font-family: DejaVu Sans, sans-serif;">PDV</td>
                        <td class="totals-value" style="font-family: DejaVu Sans, sans-serif;">{{ $formatAmount($invoice->tax_total) }} {{ $currency }}</td>
                    </tr>
                    @endif
                    <tr class="total-row">
                        <td>UKUPNO ZA PLAĆANJE</td>
                        <td class="totals-value">{{ $formatAmount($invoice->total) }} {{ $currency }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="amount-in-words">
        Slovima:
        @php
            // PHP na uređaju je bez ICU-a, pa se iznos slovima računa sam.
            $spelled = \App\Support\SpelledAmount::of(intdiv($invoice->total, 100));
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
        Hvala na poslovanju | {{ $company->name }}
    </div>

</div>
@if($smallNote)
    <div style="padding: 0 28px 16px 28px; font-size: 7pt; font-style: italic;">{{ $smallNote }}</div>
@endif
</body>
</html>
