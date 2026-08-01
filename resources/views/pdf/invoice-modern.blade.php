@php
    $formatAmount = fn ($pfening) => number_format($pfening / 100, 2, ',', '.');
    $currency = $invoice->currencySymbol();
    // Porez se prikazuje i kad kompanija nije obveznik, ako ga na računu ima —
    // inače osnovica i ukupno ne bi bili u vezi.
    $showVat = ($company->is_vat_obligor ?? true) || $invoice->tax_total > 0;
    $smallNote = ($company->is_small_entrepreneur ?? false) ? trim((string) $company->small_entrepreneur_note) : '';

    // Boje iz dizajna
    $color_primary = '#2f80ed';
    $color_bg_light = '#f3f8fb';
    $color_border = '#e5eaf0';
    $color_text_dark = '#111827';
    $color_text_muted = '#6b7280';

@endphp
    <!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="utf-8">
    <style>
        /* DomPDF A4 – ne postavljati height na body da ne bi nastala prazna stranica 2 */
        @page { margin: 0; }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        html, body { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }

        body {
            font-family: 'DejaVu Sans', 'Helvetica', 'Arial', sans-serif;
            font-size: 9pt;
            color: {{ $color_text_dark }};
            background: #fff;
            width: 210mm;
        }

        /* Tanka akcentna traka na vrhu stranice */
        .accent-stripe {
            height: 5px;
            background-color: {{ $color_primary }};
        }

        .page-wrapper {
            padding: 28px 50px 32px 50px;
            position: relative;
        }

        /* Header section – lijevo: logo avatar + ime; desno: detalji kompanije */
        .header-table { width: 100%; margin-bottom: 12px; border-collapse: collapse; padding-bottom: 10px; border-bottom: 1px solid {{ $color_border }}; }
        .header-table td { vertical-align: middle; }
        .header-left { width: 50%; }
        .header-right { width: 50%; text-align: right; }
        .logo-avatar {
            width: 46px;
            height: 46px;
            background: {{ $color_primary }};
            border-radius: 50%;
            color: #fff;
            font-size: 18pt;
            font-weight: bold;
            text-align: center;
            line-height: 46px;
            display: inline-block;
        }
        .company-name-left { font-size: 15pt; font-weight: bold; color: {{ $color_text_dark }}; letter-spacing: -0.3px; }
        .company-info-right { font-size: 8pt; color: {{ $color_text_muted }}; line-height: 1.35; text-align: right; }

        /* Oznaka sekcije (eyebrow) iznad naslova boxa */
        .card-eyebrow {
            font-size: 7pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: {{ $color_primary }};
            margin-bottom: 8px;
        }

        /* Client card */
        .client-card {
            background: {{ $color_bg_light }};
            border-radius: 12px;
            padding: 14px 18px;
        }
        .client-card .client-name { font-size: 10pt; font-weight: bold; margin-bottom: 4px; color: {{ $color_text_dark }}; }
        .client-card .client-detail { font-size: 8pt; color: {{ $color_text_muted }}; line-height: 1.55; }

        /* Dva boxa u jednom redu: Klijent lijevo, Detalji desno */
        .client-details-row { width: 100%; border-collapse: collapse; margin-bottom: 16px; table-layout: fixed; }
        .client-details-row td { vertical-align: top; padding: 0; }
        .client-details-row td:first-child { padding-right: 10px; width: 50%; }
        .client-details-row td:last-child { padding-left: 10px; width: 50%; }
        .client-details-row .client-card,
        .client-details-row .document-details-box {
            height: 115px;
            min-height: 115px;
            box-sizing: border-box;
        }
        .document-details-box {
            background: #fff;
            border-radius: 12px;
            border: 1px solid {{ $color_border }};
            padding: 12px 18px;
        }
        .document-details-box .document-details-row {
            padding: 4px 0;
            font-size: 8pt;
            border-bottom: 1px dashed {{ $color_border }};
        }
        .document-details-box .document-details-row:last-child { border-bottom: none; }
        .document-details-box .document-details-label { color: {{ $color_text_muted }}; text-transform: uppercase; letter-spacing: 0.4px; font-size: 7pt; }
        .document-details-box .document-details-value { font-weight: bold; color: {{ $color_text_dark }}; }

        /* Naslov dokumenta + status chip */
        .document-title-bar { margin-bottom: 12px; padding-bottom: 10px; border-bottom: 1px solid {{ $color_border }}; }
        .document-title-line { font-size: 22pt; font-weight: bold; color: {{ $color_text_dark }}; letter-spacing: -0.5px; }
        .document-title-line .document-title-number { color: {{ $color_primary }}; }
        .status-chip {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 7.5pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            vertical-align: middle;
            margin-left: 12px;
        }

        /* MAIN INVOICE TABLE */
        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .invoice-table thead th {
            font-size: 7.5pt;
            color: {{ $color_text_muted }};
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            padding: 12px 14px;
            background-color: {{ $color_bg_light }};
            border-bottom: 2px solid {{ $color_primary }};
        }
        .invoice-table th.text-right,
        .invoice-table td.text-right { text-align: right; }
        .invoice-table th.text-center,
        .invoice-table td.text-center { text-align: center; }

        .invoice-table tbody td {
            padding: 11px 14px;
            vertical-align: top;
            border-bottom: 1px solid {{ $color_border }};
            font-size: 9pt;
        }
        .invoice-table tbody tr:nth-child(even) td {
            background-color: {{ $color_bg_light }};
        }
        .invoice-table tbody tr:last-child td {
            border-bottom: 1px solid {{ $color_border }};
        }
        .cell-description .item-name { font-weight: bold; color: {{ $color_text_dark }}; }
        .cell-description .item-desc { font-size: 7.5pt; color: {{ $color_text_muted }}; margin-top: 2px; }

        .text-right { text-align: right; }
        .text-center { text-align: center; }

        /* SUMMARY */
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-top: 12px;
        }
        .summary-col-left { vertical-align: top; padding-right: 0; }
        .summary-col-right {
            vertical-align: top;
            background-color: {{ $color_bg_light }};
            padding: 10px 16px 14px 16px;
            border-radius: 12px;
        }

        .summary-item-table {
            width: 100%;
            border-collapse: collapse;
        }
        .summary-label {
            font-size: 9pt;
            color: {{ $color_text_muted }};
            padding: 5px 0;
            text-align: left;
        }
        .summary-value {
            font-size: 9pt;
            font-weight: bold;
            padding: 5px 0;
            text-align: right;
            color: {{ $color_text_dark }};
        }
        .summary-row-line { }

        /* TOTAL BLUE BOX */
        .total-blue-box {
            background-color: {{ $color_primary }};
            color: #ffffff;
            padding: 10px 16px;
            margin: 6px -16px -14px -16px;
            border-bottom-left-radius: 12px;
            border-bottom-right-radius: 12px;
        }
        .total-blue-box .total-label { font-size: 9.5pt; font-weight: bold; color: #ffffff; text-transform: uppercase; letter-spacing: 0.8px; }
        .total-blue-box .total-value { font-size: 12.5pt; font-weight: bold; color: #ffffff; }

        .amount-in-words {
            font-size: 7.5pt;
            color: {{ $color_text_muted }};
            margin-top: 8px;
            text-align: right;
            font-style: italic;
        }

        .notes-section {
            margin-top: 12px;
            padding: 10px 14px;
            background: {{ $color_bg_light }};
            border-radius: 15px;
        }
        .notes-section .notes-label {
            font-size: 8pt; font-weight: bold; color: {{ $color_text_dark }};
            padding-bottom: 8px;
            border-bottom: 1px solid #d1d5db;
            margin-bottom: 8px;
        }
        .notes-section .notes-text { font-size: 8pt; color: {{ $color_text_muted }}; line-height: 1.4; }

        /* Signature section */
        .signature-section { margin-top: 45px; width: 100%; border-collapse: collapse; }
        .signature-section td {
            width: 50%;
            text-align: center;
            font-size: 8pt;
            color: {{ $color_text_muted }};
            padding: 0 20px;
        }
        .signature-mp { font-size: 7pt; color: {{ $color_text_muted }}; margin-bottom: 18px; letter-spacing: 0.6px; }
        .signature-line { border-bottom: 1px solid {{ $color_text_dark }}; width: 180px; margin: 0 auto 6px auto; }
        .signature-label { text-transform: uppercase; font-weight: bold; letter-spacing: 0.6px; color: {{ $color_text_dark }}; font-size: 7.5pt; }

        /* Footer positioning */
        .footer-container { margin-top: 18px; }

        .payment-card {
            background: {{ $color_bg_light }};
            border-radius: 12px;
            padding: 10px 14px;
            min-width: 280px;
            max-width: 320px;
        }
        .payment-card .bank-name { font-size: 9pt; font-weight: bold; margin-bottom: 2px; }
        .payment-card .bank-detail { font-size: 8pt; color: {{ $color_text_muted }}; line-height: 1.4; }
        .payment-card .bank-contact { margin-top: 6px; padding-top: 6px; border-top: 1px solid #d1d5db; font-size: 8pt; color: {{ $color_text_muted }}; line-height: 1.3; word-wrap: break-word; }
    </style>
</head>
<body>

<div class="accent-stripe"></div>

<div class="page-wrapper">

    <table class="header-table">
        <tr>
            <td class="header-left">
                <table style="border-collapse: collapse; border: none;"><tr>
                    <td style="vertical-align: middle;">
                        <div class="company-name-left">{{ $company->name }}</div>
                    </td>
                </tr></table>
            </td>
            <td class="header-right">
                @if($company->address || $company->city || $company->identification_number)
                    <div class="company-info-right">
                        {{ $company->name }}<br>
                        @if($company->address){{ $company->address }}<br>@endif
                        @if($company->zip || $company->city){{ $company->zip }} {{ $company->city }}@if($company->country), {{ $company->country }}@endif<br>@endif
                        @if($company->identification_number) JIB: {{ $company->identification_number }}<br>@endif
                        @if($company->vat_number) PDV: {{ $company->vat_number }}@endif
                    </div>
                @endif
            </td>
        </tr>
    </table>

    <table class="client-details-row">
        <tr>
            <td>
                <div class="client-card">
                    <div class="card-eyebrow">Kupac</div>
                    <div class="client-name">{{ $invoice->client?->name }}</div>
                    <div class="client-detail">
                        @if($invoice->client?->address){{ $invoice->client->address }}<br>@endif
                        @if($invoice->client?->city){{ $invoice->client->city }}<br>@endif
                        @if($invoice->client?->country){{ $invoice->client->country }}<br>@endif
                        @if($invoice->client?->phone){{ $invoice->client->phone }}<br>@endif
                        @if($invoice->client?->vat_id)JIB: {{ $invoice->client->vat_id }}<br>@endif
                        @if($invoice->client?->tax_id)PDV: {{ $invoice->client->tax_id }}@endif
                    </div>
                </div>
            </td>
            <td>
                <div class="document-details-box">
                    <div class="card-eyebrow">Detalji</div>
                    <div class="document-details-row"><span class="document-details-label">Rok dospijeća</span> <span class="document-details-value">{{ $invoice->due_date?->format('d.m.Y.') ?? '-' }}</span></div>
                    <div class="document-details-row"><span class="document-details-label">Datum izdavanja</span> <span class="document-details-value">{{ $invoice->date?->format('d.m.Y.') ?? '-' }}</span></div>
                    <div class="document-details-row"><span class="document-details-label">Način plaćanja</span> <span class="document-details-value">{{ $invoice->payment_type?->label() ?? '-' }}</span></div>
                    @php
                        $originalFiscal = $invoice->originalFiscalRecord();
                    @endphp
                    @if($originalFiscal?->fiscal_invoice_number)
                        <div class="document-details-row"><span class="document-details-label">Br. fiskalnog računa</span> <span class="document-details-value">{{ $originalFiscal->fiscal_invoice_number }}</span></div>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <div class="document-title-bar">
        <div class="document-title-line">
            Račun <span class="document-title-number">{{ $invoice->invoice_number }}</span>
        </div>
    </div>

    <table class="invoice-table">
        <thead>
        <tr>
            <th style="width:4%;" class="text-center">#</th>
            <th style="width:{{ $showVat ? '24%' : '46%' }};">Naziv</th>
            <th style="width:6%;" class="text-center">JM</th>
            <th style="width:6%;" class="text-center">Kol.</th>
            <th style="width:{{ $showVat ? '8%' : '14%' }};" class="text-right">Cijena</th>
            @if($showVat)
                <th style="width:10%;" class="text-right">Cijena sa PDV</th>
                <th style="width:6%;" class="text-center">PDV %</th>
                <th style="width:10%;" class="text-right">Iznos PDV-a</th>
                <th style="width:10%;" class="text-right">Iznos bez PDV</th>
                <th style="width:14%;" class="text-right th-panel-end">Iznos sa PDV</th>
            @else
                <th style="width:24%;" class="text-right th-panel-end">Iznos</th>
            @endif
        </tr>
        </thead>
        <tbody>
        @foreach($invoice->items as $item)
            @php
                $quantity = (float)($item->quantity ?? 0);
                $taxRateRaw = (int)($item->tax_rate ?? 0);
                $unitPriceWithVat = (int)$item->unit_price;
                $subtotal = (int)$item->subtotal;
                $taxAmount = (int)$item->tax_amount;
                $total = (int)$item->total;
                $unitPriceWithoutVat = $quantity > 0 ? (int)round($subtotal / $quantity) : 0;
                $unit = $item->unit->label();
                $taxRatePercent = $taxRateRaw / 100;
            @endphp
            <tr>
                <td class="text-center">{{ $loop->iteration }}</td>
                <td class="cell-description">
                    <div class="item-name">{{ $item->name }}</div>
                    @if($item->description)<div class="item-desc">{!! nl2br(e($item->description)) !!}</div>@endif
                </td>
                <td class="text-center">{{ $unit }}</td>
                <td class="text-center">{{ rtrim(rtrim(number_format($quantity, 3, ',', '.'), '0'), ',') }}</td>
                @if($showVat)
                    <td class="text-right">{{ $formatAmount($unitPriceWithoutVat) }}</td>
                    <td class="text-right">{{ $formatAmount($unitPriceWithVat) }}</td>
                    <td class="text-right">{{ rtrim(rtrim(number_format($taxRatePercent, 2, ',', '.'), '0'), ',') }}%</td>
                    <td class="text-right">{{ $formatAmount($taxAmount) }}</td>
                    <td class="text-right">{{ $formatAmount($subtotal) }}</td>
                    <td class="text-right" style="font-weight: bold; color: {{ $color_primary }};">{{ $formatAmount($total) }} {{ $currency }}</td>
                @else
                    <td class="text-right">{{ $formatAmount($quantity > 0 ? (int)round($total / $quantity) : 0) }}</td>
                    <td class="text-right" style="font-weight: bold; color: {{ $color_primary }};">{{ $formatAmount($total) }} {{ $currency }}</td>
                @endif
            </tr>
        @endforeach
        </tbody>
    </table>

    <table class="summary-table">
        <tr>
            <td class="summary-col-left" style="width: {{ $showVat ? '28%' : '50%' }};"></td>
            <td class="summary-col-right" style="width: {{ $showVat ? '72%' : '50%' }};">
                <table class="summary-item-table">
                    <tr class="summary-row-line">
                        <td class="summary-label">Osnovica:</td>
                        <td class="summary-value">{{ $formatAmount($invoice->subtotal) }} {{ $currency }}</td>
                    </tr>

                    @if($showVat)
                        <tr class="summary-row-line" style="border-bottom: none;">
                            <td class="summary-label">PDV:</td>
                            <td class="summary-value">{{ $formatAmount($invoice->tax_total) }} {{ $currency }}</td>
                        </tr>
                    @endif
                </table>

                <div class="total-blue-box">
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td class="total-label">Ukupno</td>
                            <td class="total-value" style="text-align: right;">
                                {{ $formatAmount($invoice->total) }} {{ $currency }}
                            </td>
                        </tr>
                    </table>
                </div>
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

    <div class="footer-container">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="vertical-align: top; width: 55%;"></td>
                <td class="text-right" style="vertical-align: top; width: 45%;">
                    <div class="payment-card">
                        <div style="font-weight: bold; font-size: 8pt; margin-bottom: 10px; text-transform: uppercase; color: {{ $color_text_dark }};">Detalji plaćanja</div>
                        @if(isset($bankAccounts) && $bankAccounts->isNotEmpty())
                            @foreach($bankAccounts as $acc)
                                <div class="bank-name">{{ $acc->bank_name }}</div>
                                <div class="bank-detail">{{ $acc->bank_name }}: {{ $acc->account_number }}</div>
                                @if($acc->swift)<div class="bank-detail">SWIFT: {{ $acc->swift }}</div>@endif
                                @if(!$loop->last)<div style="height: 8px;"></div>@endif
                            @endforeach
                        @else
                            <div class="bank-detail">Nije unesen bankovni račun.</div>
                        @endif
                        <div class="bank-contact">
                            @if($company->phone){{ $company->phone }}@endif
                            @if($company->phone && $company->email) | @endif
                            @if($company->email){{ $company->email }}@endif
                        </div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <table class="signature-section">
        <tr>
            <td>
                <div class="signature-mp">M.P.</div>
                <div class="signature-line"></div>
                <div class="signature-label">Izdao</div>
            </td>
            <td>
                <div class="signature-mp">M.P.</div>
                <div class="signature-line"></div>
                <div class="signature-label">Primio</div>
            </td>
        </tr>
    </table>

</div>

@if($smallNote)
    <div style="padding: 0 28px 16px 28px; font-size: 7pt; font-style: italic;">{{ $smallNote }}</div>
@endif
</body>
</html>
