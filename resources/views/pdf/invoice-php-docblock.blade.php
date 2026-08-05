@php
    $f = fn (int $v): string => number_format($v / 100, 2, ',', '.');
    $q = fn ($v): string => rtrim(rtrim(number_format((float) $v, 3, ',', '.'), '0'), ',');
    $c = $invoice->currencySymbol();
    $showVat = ($company->is_vat_obligor ?? true) || $invoice->tax_total > 0;
    $fiscal = $invoice->originalFiscalRecord();
    $spelled = \App\Support\SpelledAmount::of(intdiv($invoice->total, 100));
@endphp
<!doctype html>
<html lang="sr">
<head>
    <meta charset="utf-8">
    <title>Račun {{ $invoice->invoice_number }}</title>
    <style>
        @page { margin: 0 }
        * { box-sizing: border-box }
        body { margin: 0; background: #f4efe2; color: #3b342a; font-family: DejaVu Sans Mono, monospace; font-size: 7.6pt; line-height: 1.35 }
        .sheet { margin: 8mm 9mm; padding: 7mm 8mm 40mm; min-height: 222mm; background: #fffdf7; border: 1px solid #e3d9c2 }
        .doc { width: 174mm; border-collapse: collapse }
        .doc .st { width: 6mm; padding: 0.4mm 0; color: #cbbf9f; text-align: center; vertical-align: top }
        .doc .ln { width: 168mm; padding: 0.4mm 0 0.4mm 3mm; vertical-align: top }
        .op { color: #b5a785 }
        .hd { width: 165mm; border-collapse: collapse }
        .hd .ti { width: 118mm; vertical-align: bottom }
        .hd h1 { margin: 0; color: #2e2820; font-family: DejaVu Sans, sans-serif; font-size: 18pt; letter-spacing: -0.4px; white-space: nowrap }
        .hd .who { margin-top: 1mm; color: #7d7361; font-size: 7pt }
        .stamp { width: 41mm; padding: 2.4mm 3mm; background: #f5edda; border: 1px solid #e0d3b4; color: #8a6d1f; text-align: center; font-size: 6.6pt; line-height: 1.5; vertical-align: top }
        .stamp b { display: block; color: #3b342a; font-size: 8.5pt }
        .rule { border-bottom: 1px solid #eae0c9; font-size: 0; line-height: 0 }
        .row { width: 165mm; border-collapse: collapse }
        .row .tag { width: 26mm; padding: 0; color: #9a6700; white-space: nowrap; vertical-align: top }
        .row .val { width: 139mm; padding: 0; vertical-align: top }
        .tag { color: #9a6700 }
        .val b { color: #2e2820 }
        .dim { color: #857b6a }
        .items { width: 174mm; border-collapse: collapse }
        .items th { padding: 1.5mm; border-bottom: 1px solid #ddd0b1; color: #9a6700; text-align: left; font-size: 6.2pt; font-weight: normal; text-transform: uppercase; letter-spacing: 0.4px }
        .items td { padding: 2mm 1.5mm; border-bottom: 1px dotted #e2d8c2; vertical-align: top }
        .items .st { width: 6mm; padding-left: 0; padding-right: 0; color: #cbbf9f; text-align: center }
        .items .nm { padding-left: 3mm }
        .r { text-align: right; white-space: nowrap }
        .cn { text-align: center; white-space: nowrap }
        .nm b { font-weight: bold }
        .ds { color: #857b6a; font-size: 6.2pt }
        .ret { width: 64mm; margin-left: auto; border-collapse: collapse }
        .ret .k { width: 30mm; padding: 1mm 0; color: #9a6700 }
        .ret .v { width: 34mm; padding: 1mm 0; text-align: right; font-weight: bold }
        .ret .all .k, .ret .all .v { padding-top: 1.8mm; border-top: 3px double #b99b4a; color: #7a5c12; font-size: 10.5pt }
        .note { white-space: pre-line }
    </style>
</head>
<body>
<div class="sheet">
    <table class="doc">
        <tr><td class="st">&nbsp;</td><td class="ln op">/**</td></tr>
        <tr>
            <td class="st">*</td>
            <td class="ln">
                <table class="hd">
                    <tr>
                        <td class="ti">
                            <h1>Račun {{ $invoice->invoice_number }}</h1>
                            <div class="who">{{ $company->name }}@if($company->city) · {{ $company->city }}@endif</div>
                        </td>
                        <td class="stamp">izdan<b>{{ $invoice->date?->format('d.m.Y.') }}</b>rok {{ $invoice->due_date?->format('d.m.Y.') }}</td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr><td class="st">*</td><td class="ln rule">&nbsp;</td></tr>
        <tr><td class="st">*</td><td class="ln">&nbsp;</td></tr>
        <tr>
            <td class="st">*</td>
            <td class="ln">
                <table class="row">
                    <tr>
                        <td class="tag">@@izdavalac</td>
                        <td class="val"><b>{{ $company->name }}</b>@if($company->address)<br><span class="dim">{{ $company->address }}@if($company->zip || $company->city), {{ trim($company->zip.' '.$company->city) }}@endif @if($company->country), {{ $company->country }}@endif</span>@endif @if($company->identification_number || $company->vat_number)<br><span class="dim">@if($company->identification_number)<span class="tag">@@jib</span> {{ $company->identification_number }}&nbsp;&nbsp;@endif @if($company->vat_number)<span class="tag">@@pdv</span> {{ $company->vat_number }}@endif</span>@endif @if($company->phone || $company->email)<br><span class="dim">{{ implode(' · ', array_filter([$company->phone, $company->email])) }}</span>@endif</td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr><td class="st">*</td><td class="ln">&nbsp;</td></tr>
        <tr>
            <td class="st">*</td>
            <td class="ln">
                <table class="row">
                    <tr>
                        <td class="tag">@@klijent</td>
                        <td class="val"><b>{{ $invoice->client?->name }}</b>@if($invoice->client?->address)<br><span class="dim">{{ $invoice->client->address }}@if($invoice->client?->zip || $invoice->client?->city), {{ trim($invoice->client->zip.' '.$invoice->client->city) }}@endif @if($invoice->client?->country), {{ $invoice->client->country }}@endif</span>@endif @if($invoice->client?->vat_id || $invoice->client?->tax_id)<br><span class="dim">@if($invoice->client?->vat_id)<span class="tag">@@jib</span> {{ $invoice->client->vat_id }}&nbsp;&nbsp;@endif @if($invoice->client?->tax_id)<span class="tag">@@pdv</span> {{ $invoice->client->tax_id }}@endif</span>@endif @if($invoice->client?->phone)<br><span class="dim">{{ $invoice->client->phone }}</span>@endif</td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr><td class="st">*</td><td class="ln">&nbsp;</td></tr>
        <tr>
            <td class="st">*</td>
            <td class="ln">
                <table class="row">
                    <tr>
                        <td class="tag">@@plaćanje</td>
                        <td class="val"><b>{{ $invoice->payment_type?->label() }}</b> <span class="dim">· valuta {{ $invoice->currency }} · rok {{ $invoice->due_date?->format('d.m.Y.') }}</span>@if($fiscal?->fiscal_invoice_number)<br><span class="dim"><span class="tag">@@fiskalni</span> {{ $fiscal->fiscal_invoice_number }}</span>@endif @if(isset($bankAccounts) && $bankAccounts->isNotEmpty())@foreach($bankAccounts as $account)<br><span class="dim">{{ $account->bank_name }} — {{ $account->account_number }}@if($account->swift) · SWIFT {{ $account->swift }}@endif</span>@endforeach @endif</td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr><td class="st">*</td><td class="ln">&nbsp;</td></tr>
    </table>

    <table class="items">
        <thead>
        <tr>
            <th class="st">*</th>
            <th class="nm" style="width:{{ $showVat ? '55mm' : '84mm' }}">@@stavka</th>
            <th class="cn" style="width:{{ $showVat ? '10mm' : '12mm' }}">jm</th>
            <th class="r" style="width:{{ $showVat ? '11mm' : '13mm' }}">kol.</th>
            <th class="r" style="width:{{ $showVat ? '18mm' : '21mm' }}">cijena</th>
            @if($showVat)
                <th class="r" style="width:10mm">pdv</th>
                <th class="r" style="width:20mm">osnovica</th>
            @endif
            <th class="r" style="width:23mm">iznos</th>
        </tr>
        </thead>
        <tbody>
        @foreach($invoice->items as $item)
            @php
                $quantity = (float) ($item->quantity ?? 0);
                $subtotal = (int) $item->subtotal;
                $unitNet = $quantity > 0 ? (int) round($subtotal / $quantity) : 0;
                $unitGross = $quantity > 0 ? (int) round(((int) $item->total) / $quantity) : 0;
            @endphp
            <tr>
                <td class="st">*</td>
                <td class="nm"><b>{{ $item->name }}</b>@if($item->description)<br><span class="ds">{!! nl2br(e($item->description)) !!}</span>@endif</td>
                <td class="cn">{{ $item->unit?->label() }}</td>
                <td class="r">{{ $q($quantity) }}</td>
                <td class="r">{{ $f($showVat ? $unitNet : $unitGross) }}</td>
                @if($showVat)
                    <td class="r">{{ $q(((int) ($item->tax_rate ?? 0)) / 100) }}%</td>
                    <td class="r">{{ $f($subtotal) }}</td>
                @endif
                <td class="r"><strong>{{ $f((int) $item->total) }}</strong></td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <table class="doc">
        <tr><td class="st">*</td><td class="ln">&nbsp;</td></tr>
        <tr>
            <td class="st">*</td>
            <td class="ln">
                <table class="ret">
                    <tr><td class="k">@@osnovica</td><td class="v">{{ $f($invoice->subtotal) }} {{ $c }}</td></tr>
                    @if($showVat)
                        <tr><td class="k">@@pdv</td><td class="v">{{ $f($invoice->tax_total) }} {{ $c }}</td></tr>
                    @endif
                    <tr class="all"><td class="k">@@ukupno</td><td class="v">{{ $f($invoice->total) }} {{ $c }}</td></tr>
                </table>
            </td>
        </tr>
        <tr><td class="st">*</td><td class="ln">&nbsp;</td></tr>
        <tr>
            <td class="st">*</td>
            <td class="ln">
                <table class="row">
                    <tr>
                        <td class="tag">@@slovima</td>
                        <td class="val dim">{{ $spelled ?? number_format(intdiv($invoice->total, 100), 0, ',', '.') }} {{ $c }} i {{ str_pad($invoice->total % 100, 2, '0', STR_PAD_LEFT) }}/100</td>
                    </tr>
                </table>
            </td>
        </tr>
        @if($invoice->notes)
            <tr><td class="st">*</td><td class="ln">&nbsp;</td></tr>
            <tr>
                <td class="st">*</td>
                <td class="ln">
                    <table class="row">
                        <tr>
                            <td class="tag">@@napomena</td>
                            <td class="val note">{{ trim((string) $invoice->notes) }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        @endif
        <tr><td class="st">&nbsp;</td><td class="ln op">*/</td></tr>
    </table>
</div>
</body>
</html>
