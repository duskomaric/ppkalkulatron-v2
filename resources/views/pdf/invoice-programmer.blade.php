@php
    $formatAmount = fn (int $pfening): string => number_format($pfening / 100, 2, ',', '.');
    $currency = $invoice->currencySymbol();
    $showVat = ($company->is_vat_obligor ?? true) || $invoice->tax_total > 0;
    $smallNote = ($company->is_small_entrepreneur ?? false) ? trim((string) $company->small_entrepreneur_note) : '';
    $fiscal = $invoice->originalFiscalRecord();
@endphp
<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="utf-8">
    <title>Račun {{ $invoice->invoice_number }}</title>
    <style>
        @page { margin: 0; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #172033; font-family: DejaVu Sans, sans-serif; font-size: 8.5pt; line-height: 1.45; }
        .page { padding: 0 17mm 18mm; }
        .hero { margin: 0 -17mm 13mm; padding: 15mm 17mm 12mm; background: #111a2e; color: #f8fafc; }
        .hero-table, .meta-grid, .items, .summary, .signatures { width: 100%; border-collapse: collapse; }
        .hero-table td { vertical-align: top; }
        .hero-company { width: 57%; }
        .hero-invoice { width: 43%; text-align: right; }
        .brand { margin-bottom: 5px; color: #67e8f9; font-family: DejaVu Sans Mono, monospace; font-size: 7.5pt; font-weight: bold; letter-spacing: 1.2px; }
        .company-name { font-size: 18pt; font-weight: bold; line-height: 1.12; }
        .company-data { margin-top: 10px; color: #cbd5e1; font-size: 7.5pt; line-height: 1.55; }
        .document-kind { color: #93c5fd; font-family: DejaVu Sans Mono, monospace; font-size: 7pt; letter-spacing: 1px; text-transform: uppercase; }
        .document-number { margin: 5px 0 8px; font-size: 22pt; font-weight: bold; letter-spacing: -0.5px; }
        .document-date { color: #cbd5e1; font-size: 8pt; }
        .meta-grid { margin-bottom: 11mm; table-layout: fixed; }
        .meta-grid td { width: 50%; padding: 0; vertical-align: top; }
        .meta-grid td:first-child { padding-right: 8mm; }
        .meta-grid td:last-child { padding-left: 8mm; border-left: 1px solid #cbd5e1; }
        .section-kicker { margin-bottom: 6px; color: #0f766e; font-family: DejaVu Sans Mono, monospace; font-size: 7pt; font-weight: bold; letter-spacing: 1px; text-transform: uppercase; }
        .recipient-name { margin-bottom: 3px; font-size: 11pt; font-weight: bold; }
        .recipient-lines { color: #475569; font-size: 8pt; line-height: 1.55; }
        .facts { width: 100%; border-collapse: collapse; }
        .facts td { padding: 2px 0; border: 0; font-size: 8pt; }
        .facts td:last-child { text-align: right; font-weight: bold; }
        .facts .label { color: #64748b; }
        .items { margin-bottom: 9mm; table-layout: fixed; border-top: 2px solid #172033; }
        .items th { padding: 8px 5px; background: #eaf4f5; color: #334155; font-family: DejaVu Sans Mono, monospace; font-size: 6.5pt; letter-spacing: .5px; text-align: left; text-transform: uppercase; }
        .items td { padding: 8px 5px; border-bottom: 1px solid #dbe4ea; vertical-align: top; }
        .items .num { text-align: right; white-space: nowrap; }
        .items .center { text-align: center; white-space: nowrap; }
        .item-name { font-weight: bold; }
        .item-description { margin-top: 2px; color: #64748b; font-size: 7pt; }
        .item-total { color: #0f766e; font-weight: bold; }
        .summary { margin: 0 0 9mm auto; width: 47%; border: 1px solid #cbd5e1; }
        .summary td { padding: 6px 9px; }
        .summary .summary-label { color: #64748b; }
        .summary .summary-value { text-align: right; font-weight: bold; }
        .summary .grand-total td { padding: 10px 9px; background: #0f766e; color: #fff; font-size: 12pt; font-weight: bold; }
        .summary .grand-total .summary-label { color: #ccfbf1; font-family: DejaVu Sans Mono, monospace; font-size: 8pt; letter-spacing: .8px; }
        .in-words { margin: -5mm 0 10mm; color: #64748b; font-size: 7pt; font-style: italic; text-align: right; }
        .note { margin-bottom: 9mm; padding: 8px 10px; border-left: 3px solid #0f766e; background: #f0fdfa; }
        .note-title { margin-bottom: 3px; color: #0f766e; font-family: DejaVu Sans Mono, monospace; font-size: 7pt; font-weight: bold; letter-spacing: .7px; text-transform: uppercase; }
        .note-text { color: #334155; font-size: 8pt; white-space: pre-line; }
        .payment { margin-bottom: 11mm; padding: 8px 10px; border: 1px solid #dbe4ea; }
        .payment-title { margin-bottom: 5px; font-family: DejaVu Sans Mono, monospace; font-size: 7pt; font-weight: bold; letter-spacing: .7px; text-transform: uppercase; }
        .payment-line { color: #475569; font-size: 7.5pt; }
        .footer { position: fixed; right: 17mm; bottom: 7mm; left: 17mm; color: #94a3b8; font-size: 6.5pt; }
    </style>
</head>
<body>
<div class="page">
    <div class="hero">
        <table class="hero-table"><tr>
            <td class="hero-company">
                <div class="brand">NAPLATA / SOFTVERSKE USLUGE</div>
                <div class="company-name">{{ $company->name }}</div>
                <div class="company-data">
                    @if($company->address){{ $company->address }}<br>@endif
                    @if($company->zip || $company->city){{ $company->zip }} {{ $company->city }}@if($company->country), {{ $company->country }}@endif<br>@endif
                    @if($company->identification_number)JIB: {{ $company->identification_number }}@endif
                    @if($company->vat_number) · PDV: {{ $company->vat_number }}@endif
                </div>
            </td>
            <td class="hero-invoice">
                <div class="document-kind">Invoice / {{ $invoice->payment_type?->label() ?? '—' }}</div>
                <div class="document-number">{{ $invoice->invoice_number }}</div>
                <div class="document-date">Izdano {{ $invoice->date?->format('d.m.Y.') ?? '—' }}</div>
            </td>
        </tr></table>
    </div>

    <table class="meta-grid"><tr>
        <td>
            <div class="section-kicker">Kupac</div>
            <div class="recipient-name">{{ $invoice->client?->name ?? '—' }}</div>
            <div class="recipient-lines">
                @if($invoice->client?->address){{ $invoice->client->address }}<br>@endif
                @if($invoice->client?->zip || $invoice->client?->city){{ $invoice->client->zip }} {{ $invoice->client->city }}<br>@endif
                @if($invoice->client?->country){{ $invoice->client->country }}<br>@endif
                @if($invoice->client?->vat_id)JIB: {{ $invoice->client->vat_id }}@endif
                @if($invoice->client?->tax_id)<br>PDV: {{ $invoice->client->tax_id }}@endif
            </div>
        </td>
        <td>
            <div class="section-kicker">Sažetak</div>
            <table class="facts">
                <tr><td class="label">Rok plaćanja</td><td>{{ $invoice->due_date?->format('d.m.Y.') ?? '—' }}</td></tr>
                <tr><td class="label">Način plaćanja</td><td>{{ $invoice->payment_type?->label() ?? '—' }}</td></tr>
                <tr><td class="label">Valuta</td><td>{{ $invoice->currency }}</td></tr>
                @if($fiscal?->fiscal_invoice_number)<tr><td class="label">Fiskalni račun</td><td>{{ $fiscal->fiscal_invoice_number }}</td></tr>@endif
            </table>
        </td>
    </tr></table>

    <table class="items">
        <thead><tr>
            <th style="width: 5%">#</th><th style="width: 39%">Stavka</th><th class="center" style="width: 8%">JM</th><th class="num" style="width: 8%">Kol.</th><th class="num" style="width: 13%">Cijena</th>
            @if($showVat)<th class="num" style="width: 9%">PDV</th>@endif
            <th class="num" style="width: {{ $showVat ? '18' : '27' }}%">Ukupno</th>
        </tr></thead>
        <tbody>
        @foreach($invoice->items as $index => $item)
            @php
                $total = $item->total;
                $quantity = max(1, (int) $item->quantity);
                $unitPrice = (int) round($total / $quantity);
            @endphp
            <tr>
                <td class="center">{{ $index + 1 }}</td>
                <td><div class="item-name">{{ $item->name }}</div>@if($item->description)<div class="item-description">{{ $item->description }}</div>@endif</td>
                <td class="center">{{ $item->unit?->value }}</td><td class="num">{{ $item->quantity }}</td>
                <td class="num">{{ $formatAmount($unitPrice) }} {{ $currency }}</td>
                @if($showVat)<td class="num">{{ number_format($item->tax_rate / 100, 2, ',', '.') }}%</td>@endif
                <td class="num item-total">{{ $formatAmount($total) }} {{ $currency }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <table class="summary">
        <tr><td class="summary-label">Osnovica</td><td class="summary-value">{{ $formatAmount($invoice->subtotal) }} {{ $currency }}</td></tr>
        @if($showVat)<tr><td class="summary-label">PDV</td><td class="summary-value">{{ $formatAmount($invoice->tax_total) }} {{ $currency }}</td></tr>@endif
        <tr class="grand-total"><td class="summary-label">UKUPNO</td><td class="summary-value">{{ $formatAmount($invoice->total) }} {{ $currency }}</td></tr>
    </table>
    @php($spelled = \App\Support\SpelledAmount::of(intdiv($invoice->total, 100)))
    <div class="in-words">Slovima: {{ $spelled ?? number_format(intdiv($invoice->total, 100), 0, ',', '.') }}</div>

    @if($invoice->notes)<div class="note"><div class="note-title">Napomena</div><div class="note-text">{{ $invoice->notes }}</div></div>@endif

    <div class="payment">
        <div class="payment-title">Podaci za plaćanje</div>
        @forelse($bankAccounts as $account)
            <div class="payment-line"><strong>{{ $account->bank_name }}</strong> · {{ $account->account_number }}@if($account->swift) · SWIFT {{ $account->swift }}@endif</div>
        @empty
            <div class="payment-line">Podaci za bankovni račun nisu uneseni.</div>
        @endforelse
    </div>
</div>
</body>
</html>
