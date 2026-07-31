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
            line-height: 1.35;
            background: #fff;
        }
        .page {
            padding: 28px 28px 50px 28px;
            max-width: 210mm;
            margin: 0 auto;
            position: relative;
            min-height: 100vh;
        }

        /* Header Table */
        .header-table {
            width: 100%;
            margin-bottom: 22px;
            padding-bottom: 14px;
            border-bottom: 2px solid #000;
            border-collapse: collapse;
        }

        .header-table td { border: none; vertical-align: middle; }
        .header-left { width: 50%; }
        .header-right { width: 50%; text-align: right; }

        .company-name-left {
            font-size: 15pt;
            font-weight: 700;
            letter-spacing: -0.3px;
        }

        .company-name {
            font-size: 11pt;
            font-weight: 700;
            margin-bottom: 2px;
        }

        .company-info {
            font-size: 6.5pt;
            line-height: 1.2;
        }

        /* Info Section Table */
        .info-table {
            width: 100%;
            margin-bottom: 20px;
            border-collapse: collapse;
        }

        .info-table td { border: none; vertical-align: top; }
        .info-left { width: 48%; padding-right: 2%; }
        .info-right { width: 48%; padding-left: 2%; }

        .info-label {
            font-size: 6pt;
            text-transform: uppercase;
            font-weight: 700;
            margin-bottom: 4px;
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

        /* Detail Table (Inside Payment) */
        .detail-table { width: 100%; border-collapse: collapse; }
        .detail-table td { border: none; padding: 1px 0; font-size: 7.5pt; }
        .detail-label { width: 50%; color: #444; }
        .detail-value { width: 50%; text-align: right; font-weight: 700; }

        /* Invoice Number Bar */
        .invoice-bar {
            width: 100%;
            padding: 8px 10px;
            margin-bottom: 15px;
            border-left: 4px solid #000;
        }

        .invoice-label {
            font-size: 12pt;
            font-weight: 700;
        }

        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }

        .items-table th {
            padding: 5px 4px;
            text-align: left;
            font-size: 6.5pt;
            text-transform: uppercase;
            font-weight: 700;
            background: #000;
            color: #fff;
        }

        .items-table td {
            padding: 4px;
            border-bottom: 0.5px solid #ccc;
            font-size: 7.5pt;
            vertical-align: top;
        }

        .items-table td.num,
        .items-table th.num {
            text-align: right;
            white-space: nowrap;
            font-variant-numeric: tabular-nums;
        }

        .items-table td.center,
        .items-table th.center {
            text-align: center;
            white-space: nowrap;
        }

        .items-table tbody tr:last-child td {
            border-bottom: 1px solid #000;
        }

        .text-right { text-align: right; }

        /* Totals */
        .totals-wrapper {
            width: 100%;
            margin-top: 10px;
            border-collapse: collapse;
        }
        .totals-right { width: 40%; }
        .totals-table { width: 100%; border-collapse: collapse; }
        .totals-table td { padding: 3px 0; font-size: 8pt; }
        .totals-value { text-align: right; font-weight: 700; }
        .total-row td {
            padding-top: 6px;
            border-top: 2px solid #000;
            font-size: 10pt;
            font-weight: 700;
        }

        /* Rest of styles... */
        .amount-in-words { margin-top: 10px; text-align: right; font-size: 7pt; font-style: italic; color: #555; }
        .notes-section { margin-top: 15px; padding: 8px; border-left: 3px solid #000; font-size: 7pt; }
        .signature-section { margin-top: 30px; width: 100%; border-collapse: collapse; }
        .signature-section td {
            text-align: center;
            font-size: 7pt;
            position: relative; /* Omogućava pozicioniranje MP */
            padding-top: 15px;   /* Prostor za pečat iznad linije */
        }

        .mp-box {
            font-size: 6pt;
            color: #888;
            margin-bottom: 25px;
        }

        .signature-line {
            border-bottom: 1px solid #000;
            width: 170px;
            margin: 0 auto 5px auto;
        }
        .footer { position: fixed; bottom: 0; left: 0; right: 0; text-align: center; font-size: 6pt; color: #999; padding: 10px; }
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
                            <td class="detail-label">Rok dospijeća:</td>
                            <td class="detail-value">{{ $invoice->due_date?->format('d.m.Y.') ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="detail-label">Datum izdavanja:</td>
                            <td class="detail-value">{{ $invoice->date?->format('d.m.Y.') ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="detail-label">Način plaćanja:</td>
                            <td class="detail-value">{{ $invoice->payment_type?->label() ?? '-' }}</td>
                        </tr>
                        @php
                            $originalFiscal = $invoice->fiscalRecords->firstWhere('type', \App\Enums\FiscalRecordType::Original);
                        @endphp
                        @if($originalFiscal?->fiscal_invoice_number)
                            <tr>
                                <td class="detail-label">Br. fiskalnog računa:</td>
                                <td class="detail-value">{{ $originalFiscal->fiscal_invoice_number }}</td>
                            </tr>
                        @endif
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <div class="invoice-bar">
        <span class="invoice-label">Račun {{ $invoice->invoice_number }}</span>
    </div>

    <table class="items-table">
        <thead>
        <tr>
            <th class="center" style="width:4%">#</th>
            <th style="width:{{ $showVat ? '28%' : '50%' }}">Naziv</th>
            <th class="center" style="width:7%">JM</th>
            <th class="num" style="width:7%">Kol.</th>
            <th class="num" style="width:{{ $showVat ? '10%' : '14%' }}">Cijena</th>
            @if($showVat)
                <th class="num" style="width:10%">Cijena sa PDV</th>
                <th class="num" style="width:8%">PDV %</th>
                <th class="num" style="width:9%">Iznos PDV-a</th>
                <th class="num" style="width:9%">Iznos bez PDV</th>
                <th class="num" style="width:8%">Iznos sa PDV</th>
            @else
                <th class="num" style="width:15%">Iznos</th>
            @endif
        </tr>
        </thead>
        <tbody>
        @foreach($invoice->items as $item)
            @php
                $quantity = (float) ($item->quantity ?? 0);
                $taxRateRaw = (int) ($item->tax_rate ?? 0); // basis points (1700 = 17%)
                $unitPriceWithVat = (int) $item->unit_price;
                $subtotal = (int) $item->subtotal; // bez PDV
                $taxAmount = (int) $item->tax_amount;
                $total = (int) $item->total; // sa PDV
                $unitPriceWithoutVat = $quantity > 0 ? (int) round($subtotal / $quantity) : 0;
                $unit = $item->unit->label();
                $taxRatePercent = $taxRateRaw / 100;
            @endphp
            <tr>
                <td class="center">{{ $loop->iteration }}</td>
                <td>
                    <div style="font-weight: 700;">{{ $item->name }}</div>
                    @if($item->description)
                        <div style="font-size: 6pt; color: #555;">{!! nl2br(e($item->description)) !!}</div>
                    @endif
                </td>
                <td class="center">{{ $unit }}</td>
                <td class="num">{{ rtrim(rtrim(number_format($quantity, 3, ',', '.'), '0'), ',') }}</td>
                @if($showVat)
                    <td class="num">{{ $formatAmount($unitPriceWithoutVat) }}</td>
                    <td class="num">{{ $formatAmount($unitPriceWithVat) }}</td>
                    <td class="num">{{ rtrim(rtrim(number_format($taxRatePercent, 2, ',', '.'), '0'), ',') }}%</td>
                    <td class="num">{{ $formatAmount($taxAmount) }}</td>
                    <td class="num">{{ $formatAmount($subtotal) }}</td>
                    <td class="num" style="font-weight: 700;">{{ $formatAmount($total) }}</td>
                @else
                    <td class="num">{{ $formatAmount($quantity > 0 ? (int) round($total / $quantity) : 0) }}</td>
                    <td class="num" style="font-weight: 700;">{{ $formatAmount($total) }}</td>
                @endif
            </tr>
        @endforeach
        </tbody>
    </table>

    <table class="totals-wrapper">
        <tr>
            <td style="width: 60%;"></td>
            <td class="totals-right">
                <table class="totals-table">
                    <tr>
                        <td class="text-right">Osnovica:</td>
                        <td class="totals-value">{{ $formatAmount($invoice->subtotal) }} {{ $currency }}</td>
                    </tr>
                    @if($showVat)
                        <tr>
                            <td class="text-right">PDV:</td>
                            <td class="totals-value">{{ $formatAmount($invoice->tax_total) }} {{ $currency }}</td>
                        </tr>
                    @endif
                    <tr class="total-row">
                        <td class="text-right">UKUPNO:</td>
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
            <strong>Napomena:</strong><br>
            {!! nl2br(e($invoice->notes)) !!}
        </div>
    @endif

    <table class="signature-section">
        <tr>
            <td class="signature-left">
                <div class="mp-box">M.P.</div>
                <div class="signature-line"></div>
                <div>Izdao</div>
            </td>
            <td class="signature-right">
                <div class="mp-box">M.P.</div>
                <div class="signature-line"></div>
                <div>Primio</div>
            </td>
        </tr>
    </table>

    <div class="footer">
        {{ $company->name }} — {{ $company->address }}, {{ $company->city }}
    </div>

</div>
@if($smallNote)
    <div style="padding: 0 28px 16px 28px; font-size: 7pt; font-style: italic;">{{ $smallNote }}</div>
@endif
</body>
</html>
