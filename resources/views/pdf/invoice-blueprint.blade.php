@php
    $formatAmount = fn (int $value): string => number_format($value / 100, 2, ',', '.');
    $currency = $invoice->currencySymbol();
    $showVat = ($company->is_vat_obligor ?? true) || $invoice->tax_total > 0;
    $fiscal = $invoice->originalFiscalRecord();
@endphp
<!DOCTYPE html>
<html lang="sr"><head><meta charset="utf-8"><title>Račun {{ $invoice->invoice_number }}</title>
<style>
    @page { margin: 0; }
    * { box-sizing: border-box; }
    body { margin: 0; color: #12304a; font-family: DejaVu Sans, sans-serif; font-size: 8pt; }
    .page { padding: 14mm 15mm 17mm 23mm; }
    .rail { position: fixed; top: 0; bottom: 0; left: 0; width: 11mm; background: #0b6f8c; }
    .rail-mark { position: fixed; bottom: 15mm; left: 2.3mm; color: #b7eff7; font-family: DejaVu Sans Mono, monospace; font-size: 6pt; letter-spacing: .9px; transform: rotate(-90deg); transform-origin: left top; white-space: nowrap; }
    .header, .identity, .items, .bottom { width: 100%; border-collapse: collapse; }
    .header { margin-bottom: 9mm; border-bottom: 2px solid #0b6f8c; }
    .header td { padding-bottom: 7mm; vertical-align: top; }
    .header .issuer { width: 56%; }
    .header .document { width: 44%; text-align: right; }
    .eyebrow { color: #0b6f8c; font-family: DejaVu Sans Mono, monospace; font-size: 6.5pt; font-weight: bold; letter-spacing: 1px; }
    .company { margin: 4px 0 6px; font-size: 16pt; font-weight: bold; }
    .muted { color: #5f7484; line-height: 1.5; }
    .invoice-word { font-size: 8pt; letter-spacing: 2px; text-transform: uppercase; }
    .invoice-number { margin-top: 5px; color: #0b6f8c; font-family: DejaVu Sans Mono, monospace; font-size: 19pt; font-weight: bold; }
    .identity { margin-bottom: 8mm; table-layout: fixed; }
    .identity td { width: 50%; vertical-align: top; padding: 7px 9px; border: 1px solid #b7cbd4; }
    .identity td:first-child { border-right: 0; }
    .block-title { margin-bottom: 5px; color: #0b6f8c; font-family: DejaVu Sans Mono, monospace; font-size: 6.5pt; font-weight: bold; letter-spacing: 1px; text-transform: uppercase; }
    .client { font-size: 10pt; font-weight: bold; }
    .facts { width: 100%; border-collapse: collapse; }
    .facts td { padding: 1px 0; }
    .facts td:last-child { text-align: right; font-weight: bold; }
    .items { margin-bottom: 7mm; border: 1px solid #b7cbd4; table-layout: fixed; }
    .items th { padding: 7px 4px; background: #e7f5f7; color: #12304a; font-family: DejaVu Sans Mono, monospace; font-size: 6pt; letter-spacing: .5px; text-align: left; text-transform: uppercase; }
    .items td { padding: 7px 4px; border-top: 1px solid #d6e3e8; vertical-align: top; }
    .items .right { text-align: right; white-space: nowrap; }
    .items .center { text-align: center; }
    .item { font-weight: bold; }
    .description { margin-top: 2px; color: #5f7484; font-size: 6.8pt; }
    .bottom td { vertical-align: top; }
    .notes { width: 54%; padding-right: 8mm; }
    .total { width: 46%; }
    .note { padding: 8px; border-top: 2px solid #0b6f8c; background: #f3fbfc; white-space: pre-line; }
    .note .block-title { margin-bottom: 3px; }
    .totals { width: 100%; border-collapse: collapse; }
    .totals td { padding: 5px 7px; border: 1px solid #b7cbd4; }
    .totals td:last-child { text-align: right; font-weight: bold; }
    .totals .grand td { background: #0b6f8c; border-color: #0b6f8c; color: #fff; font-size: 11pt; }
    .payment { margin-top: 8mm; padding-top: 5mm; border-top: 1px dashed #91a9b5; font-size: 7pt; }
    .footer { position: fixed; right: 15mm; bottom: 6mm; left: 23mm; color: #74909e; font-family: DejaVu Sans Mono, monospace; font-size: 6pt; }
</style></head><body>
<div class="rail"></div><div class="rail-mark">BLUEPRINT / {{ $invoice->invoice_number }}</div>
<div class="page">
    <table class="header"><tr><td class="issuer"><div class="eyebrow">ISSUER</div><div class="company">{{ $company->name }}</div><div class="muted">@if($company->address){{ $company->address }}<br>@endif @if($company->zip || $company->city){{ $company->zip }} {{ $company->city }}<br>@endif @if($company->identification_number)JIB: {{ $company->identification_number }}@endif @if($company->vat_number) · PDV: {{ $company->vat_number }}@endif</div></td><td class="document"><div class="invoice-word">Faktura</div><div class="invoice-number">{{ $invoice->invoice_number }}</div><div class="muted">Datum: {{ $invoice->date?->format('d.m.Y.') ?? '—' }}</div></td></tr></table>
    <table class="identity"><tr><td><div class="block-title">Kupac</div><div class="client">{{ $invoice->client?->name ?? '—' }}</div><div class="muted">@if($invoice->client?->address){{ $invoice->client->address }}<br>@endif @if($invoice->client?->zip || $invoice->client?->city){{ $invoice->client->zip }} {{ $invoice->client->city }}<br>@endif @if($invoice->client?->vat_id)JIB: {{ $invoice->client->vat_id }}@endif</div></td><td><div class="block-title">Referenca</div><table class="facts"><tr><td>Rok plaćanja</td><td>{{ $invoice->due_date?->format('d.m.Y.') ?? '—' }}</td></tr><tr><td>Način plaćanja</td><td>{{ $invoice->payment_type?->label() ?? '—' }}</td></tr><tr><td>Valuta</td><td>{{ $invoice->currency }}</td></tr>@if($fiscal?->fiscal_invoice_number)<tr><td>Fiskalni račun</td><td>{{ $fiscal->fiscal_invoice_number }}</td></tr>@endif</table></td></tr></table>
    <table class="items"><thead><tr><th style="width:5%">#</th><th style="width:39%">Opis</th><th class="center" style="width:8%">JM</th><th class="right" style="width:8%">Kol.</th><th class="right" style="width:14%">Cijena</th>@if($showVat)<th class="right" style="width:9%">PDV</th>@endif<th class="right" style="width:{{ $showVat ? '17' : '26' }}%">Iznos</th></tr></thead><tbody>@foreach($invoice->items as $index => $item) @php($quantity = max(1, (int) $item->quantity))<tr><td class="center">{{ $index + 1 }}</td><td><div class="item">{{ $item->name }}</div>@if($item->description)<div class="description">{{ $item->description }}</div>@endif</td><td class="center">{{ $item->unit?->value }}</td><td class="right">{{ $item->quantity }}</td><td class="right">{{ $formatAmount((int) round($item->total / $quantity)) }} {{ $currency }}</td>@if($showVat)<td class="right">{{ number_format($item->tax_rate / 100, 2, ',', '.') }}%</td>@endif<td class="right"><strong>{{ $formatAmount($item->total) }} {{ $currency }}</strong></td></tr>@endforeach</tbody></table>
    <table class="bottom"><tr><td class="notes">@if($invoice->notes)<div class="note"><div class="block-title">Napomena</div>{{ $invoice->notes }}</div>@endif<div class="payment"><div class="block-title">Plaćanje</div>@forelse($bankAccounts as $account)<div><strong>{{ $account->bank_name }}</strong> · {{ $account->account_number }}@if($account->swift) · SWIFT {{ $account->swift }}@endif</div>@empty<div class="muted">Podaci za bankovni račun nisu uneseni.</div>@endforelse</div></td><td class="total"><table class="totals"><tr><td>Osnovica</td><td>{{ $formatAmount($invoice->subtotal) }} {{ $currency }}</td></tr>@if($showVat)<tr><td>PDV</td><td>{{ $formatAmount($invoice->tax_total) }} {{ $currency }}</td></tr>@endif<tr class="grand"><td>UKUPNO</td><td>{{ $formatAmount($invoice->total) }} {{ $currency }}</td></tr></table></td></tr></table>
</div>
<div class="footer">BLUEPRINT · {{ $company->name }} · {{ $invoice->invoice_number }}</div>
</body></html>
