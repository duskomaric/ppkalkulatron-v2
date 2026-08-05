@php
    $f = fn (int $v): string => number_format($v / 100, 2, ',', '.');
    $q = fn ($v): string => rtrim(rtrim(number_format((float) $v, 3, ',', '.'), '0'), ',');
    $c = $invoice->currencySymbol();
    $showVat = ($company->is_vat_obligor ?? true) || $invoice->tax_total > 0;
    $fiscal = $invoice->originalFiscalRecord();
    $file = 'racun-'.str_replace('/', '-', (string) $invoice->invoice_number).'.php';
    $spelled = \App\Support\SpelledAmount::of(intdiv($invoice->total, 100));
    $ln = 0;
@endphp
<!doctype html>
<html lang="sr">
<head>
    <meta charset="utf-8">
    <title>Račun {{ $invoice->invoice_number }}</title>
    <style>
        @page { margin: 0 }
        * { box-sizing: border-box }
        body { margin: 0; background: #15151f; color: #d5d3e3; font-family: DejaVu Sans Mono, monospace; font-size: 7.2pt; line-height: 1.1 }
        .page { padding: 10mm 12mm 48mm }
        .tabbar table { width: 100%; border-collapse: collapse; border-bottom: 1px solid #2b2b3c }
        .tabbar td { padding: 0; vertical-align: bottom }
        .tab { display: inline-block; padding: 2.2mm 5mm; background: #1e1e2b; border: 1px solid #2b2b3c; border-bottom: 0; color: #cbb9f2; font-weight: bold }
        .tab i { color: #6f6a9a; font-style: normal }
        .mark { display: inline-block; padding: 1.4mm 4mm; background: #8892bf; color: #15151f; font-size: 8pt; font-weight: bold; letter-spacing: 1px }
        .src { width: 100%; border-collapse: collapse }
        .src td { padding: 0.15mm 0; vertical-align: top }
        .src .n { width: 11mm; padding-right: 3mm; background: #191924; border-right: 1px solid #2b2b3c; color: #4b4a67; text-align: right }
        .src .c { width: 175mm; padding-left: 4mm }
        .top { margin-top: 4mm }
        .i1 { padding-left: 8mm !important }
        .tg { color: #ff7bb0; font-weight: bold }
        .kw { color: #c792ea; font-weight: bold }
        .vr { color: #f78c6c }
        .st { color: #a5e075 }
        .nu { color: #82aaff }
        .cm { color: #64627f }
        .pn { color: #7a7a99 }
        .hd { color: #eae8f7; font-weight: bold }
        .items { width: 100%; border-collapse: collapse }
        .items th { padding: 1.6mm 1.5mm; background: #22222f; border-top: 1px solid #2b2b3c; border-bottom: 1px solid #322f47; color: #b39ddb; text-align: left; font-size: 6.2pt; font-weight: normal; text-transform: uppercase; letter-spacing: 0.3px }
        .items td { padding: 2mm 1.5mm; border-bottom: 1px solid #24242f; vertical-align: top; line-height: 1.3 }
        .items .g { padding-right: 3mm; background: #191924; border-right: 1px solid #2b2b3c; color: #4b4a67; text-align: right; font-size: 7.2pt }
        .items th.g { background: #191924; border-bottom-color: #2b2b3c }
        .r { text-align: right; white-space: nowrap }
        .cn { text-align: center; white-space: nowrap }
        .nm { color: #eae8f7; font-weight: bold }
        .ds { color: #6f6d8e; font-size: 6.2pt }
        .sum { width: 100%; border-collapse: collapse }
        .sum > tbody > tr > td { padding: 0; vertical-align: top }
        .sum .pad { width: 11mm; background: #191924; border-right: 1px solid #2b2b3c }
        .ret { margin: 1.5mm 0 1.5mm 4mm; padding: 2.5mm 4mm; background: #1c1c28; border-left: 3px solid #8892bf }
        .rt { width: 100%; border-collapse: collapse }
        .rt td { padding: 1.1mm 0 }
        .rt .v { text-align: right; color: #eae8f7; font-weight: bold }
        .rt .all td { padding-top: 2mm; border-top: 1px solid #322f47; color: #cbb9f2; font-size: 10pt; font-weight: bold }
    </style>
</head>
<body>
<div class="page">
    <div class="tabbar">
        <table>
            <tr>
                <td><span class="tab"><i>&lt;/&gt;</i> &nbsp;{{ $file }}</span></td>
                <td class="r"><span class="mark">php</span></td>
            </tr>
        </table>
    </div>

    <table class="src top">
        <tr><td class="n">{{ ++$ln }}</td><td class="c"><span class="tg">&lt;?php</span></td></tr>
        <tr><td class="n">{{ ++$ln }}</td><td class="c">&nbsp;</td></tr>
        <tr><td class="n">{{ ++$ln }}</td><td class="c"><span class="vr">$racun</span> <span class="pn">=</span> <span class="kw">&lt;&lt;&lt;RACUN</span></td></tr>
        <tr><td class="n">{{ ++$ln }}</td><td class="c i1"><span class="st hd">Račun {{ $invoice->invoice_number }} · izdan {{ $invoice->date?->format('d.m.Y.') }}</span></td></tr>
        <tr><td class="n">{{ ++$ln }}</td><td class="c i1"><span class="st">rok plaćanja {{ $invoice->due_date?->format('d.m.Y.') }} · {{ $invoice->payment_type?->label() }} · valuta {{ $invoice->currency }}</span></td></tr>
        @if($fiscal?->fiscal_invoice_number)
            <tr><td class="n">{{ ++$ln }}</td><td class="c i1"><span class="st">fiskalni račun {{ $fiscal->fiscal_invoice_number }}</span></td></tr>
        @endif
        <tr><td class="n">{{ ++$ln }}</td><td class="c"><span class="kw">RACUN;</span></td></tr>
        <tr><td class="n">{{ ++$ln }}</td><td class="c">&nbsp;</td></tr>
        <tr><td class="n">{{ ++$ln }}</td><td class="c"><span class="vr">$izdavalac</span> <span class="pn">= [</span></td></tr>
        <tr><td class="n">{{ ++$ln }}</td><td class="c i1"><span class="st">'naziv'</span> <span class="pn">=&gt;</span> <span class="st">'{{ $company->name }}'</span><span class="pn">,</span></td></tr>
        @if($company->address)
            <tr><td class="n">{{ ++$ln }}</td><td class="c i1"><span class="st">'adresa'</span> <span class="pn">=&gt;</span> <span class="st">'{{ $company->address }}'</span><span class="pn">,</span></td></tr>
        @endif
        @if($company->zip || $company->city)
            <tr><td class="n">{{ ++$ln }}</td><td class="c i1"><span class="st">'mjesto'</span> <span class="pn">=&gt;</span> <span class="st">'{{ trim($company->zip.' '.$company->city) }}@if($company->country), {{ $company->country }}@endif'</span><span class="pn">,</span></td></tr>
        @endif
        @if($company->identification_number)
            <tr><td class="n">{{ ++$ln }}</td><td class="c i1"><span class="st">'jib'</span> <span class="pn">=&gt;</span> <span class="nu">{{ $company->identification_number }}</span><span class="pn">,</span></td></tr>
        @endif
        @if($company->vat_number)
            <tr><td class="n">{{ ++$ln }}</td><td class="c i1"><span class="st">'pdv'</span> <span class="pn">=&gt;</span> <span class="nu">{{ $company->vat_number }}</span><span class="pn">,</span></td></tr>
        @endif
        @if($company->phone || $company->email)
            <tr><td class="n">{{ ++$ln }}</td><td class="c i1"><span class="st">'kontakt'</span> <span class="pn">=&gt;</span> <span class="st">'{{ implode(' · ', array_filter([$company->phone, $company->email])) }}'</span><span class="pn">,</span></td></tr>
        @endif
        <tr><td class="n">{{ ++$ln }}</td><td class="c"><span class="pn">];</span></td></tr>
        <tr><td class="n">{{ ++$ln }}</td><td class="c">&nbsp;</td></tr>
        <tr><td class="n">{{ ++$ln }}</td><td class="c"><span class="vr">$kupac</span> <span class="pn">= [</span></td></tr>
        <tr><td class="n">{{ ++$ln }}</td><td class="c i1"><span class="st">'naziv'</span> <span class="pn">=&gt;</span> <span class="st">'{{ $invoice->client?->name }}'</span><span class="pn">,</span></td></tr>
        @if($invoice->client?->address)
            <tr><td class="n">{{ ++$ln }}</td><td class="c i1"><span class="st">'adresa'</span> <span class="pn">=&gt;</span> <span class="st">'{{ $invoice->client->address }}'</span><span class="pn">,</span></td></tr>
        @endif
        @if($invoice->client?->zip || $invoice->client?->city)
            <tr><td class="n">{{ ++$ln }}</td><td class="c i1"><span class="st">'mjesto'</span> <span class="pn">=&gt;</span> <span class="st">'{{ trim($invoice->client->zip.' '.$invoice->client->city) }}@if($invoice->client?->country), {{ $invoice->client->country }}@endif'</span><span class="pn">,</span></td></tr>
        @endif
        @if($invoice->client?->vat_id)
            <tr><td class="n">{{ ++$ln }}</td><td class="c i1"><span class="st">'jib'</span> <span class="pn">=&gt;</span> <span class="nu">{{ $invoice->client->vat_id }}</span><span class="pn">,</span></td></tr>
        @endif
        @if($invoice->client?->tax_id)
            <tr><td class="n">{{ ++$ln }}</td><td class="c i1"><span class="st">'pdv'</span> <span class="pn">=&gt;</span> <span class="nu">{{ $invoice->client->tax_id }}</span><span class="pn">,</span></td></tr>
        @endif
        @if($invoice->client?->phone)
            <tr><td class="n">{{ ++$ln }}</td><td class="c i1"><span class="st">'telefon'</span> <span class="pn">=&gt;</span> <span class="st">'{{ $invoice->client->phone }}'</span><span class="pn">,</span></td></tr>
        @endif
        <tr><td class="n">{{ ++$ln }}</td><td class="c"><span class="pn">];</span></td></tr>
        <tr><td class="n">{{ ++$ln }}</td><td class="c">&nbsp;</td></tr>
        <tr><td class="n">{{ ++$ln }}</td><td class="c"><span class="kw">foreach</span> <span class="pn">(</span><span class="vr">$stavke</span> <span class="kw">as</span> <span class="vr">$stavka</span><span class="pn">) {</span></td></tr>
    </table>

    <table class="items">
        <colgroup><col style="width:6%"><col style="width:{{ $showVat ? '32%' : '48%' }}"><col style="width:7%"><col style="width:8%"><col style="width:12%">@if($showVat)<col style="width:8%"><col style="width:12%">@endif<col style="width:15%"></colgroup>
        <thead>
        <tr>
            <th class="g">&nbsp;</th>
            <th>stavka</th>
            <th class="cn">jm</th>
            <th class="r">kol.</th>
            <th class="r">cijena</th>
            @if($showVat)
                <th class="r">pdv %</th>
                <th class="r">osnovica</th>
            @endif
            <th class="r">iznos</th>
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
                <td class="g">{{ ++$ln }}</td>
                <td><span class="nm">{{ $item->name }}</span>@if($item->description)<br><span class="ds">{!! nl2br(e($item->description)) !!}</span>@endif</td>
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

    <table class="src">
        <tr><td class="n">{{ ++$ln }}</td><td class="c"><span class="pn">}</span></td></tr>
        <tr><td class="n">{{ ++$ln }}</td><td class="c">&nbsp;</td></tr>
        <tr><td class="n">{{ ++$ln }}</td><td class="c"><span class="kw">return</span> <span class="pn">[</span></td></tr>
    </table>

    <table class="sum">
        <tr>
            <td class="pad">&nbsp;</td>
            <td>
                <div class="ret">
                    <table class="rt">
                        <tr><td><span class="st">'osnovica'</span> <span class="pn">=&gt;</span></td><td class="v">{{ $f($invoice->subtotal) }} {{ $c }}</td></tr>
                        @if($showVat)
                            <tr><td><span class="st">'pdv'</span> <span class="pn">=&gt;</span></td><td class="v">{{ $f($invoice->tax_total) }} {{ $c }}</td></tr>
                        @endif
                        <tr class="all"><td><span class="st">'ukupno'</span> <span class="pn">=&gt;</span></td><td class="v">{{ $f($invoice->total) }} {{ $c }}</td></tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <table class="src">
        <tr><td class="n">{{ ++$ln }}</td><td class="c"><span class="pn">];</span></td></tr>
        <tr><td class="n">{{ ++$ln }}</td><td class="c">&nbsp;</td></tr>
        <tr><td class="n">{{ ++$ln }}</td><td class="c"><span class="cm">// slovima: {{ $spelled ?? number_format(intdiv($invoice->total, 100), 0, ',', '.') }} {{ $c }} i {{ str_pad($invoice->total % 100, 2, '0', STR_PAD_LEFT) }}/100</span></td></tr>
        @if($invoice->notes)
            <tr><td class="n">{{ ++$ln }}</td><td class="c">&nbsp;</td></tr>
            <tr><td class="n">{{ ++$ln }}</td><td class="c"><span class="cm">/* napomena</span></td></tr>
            @foreach(preg_split('/\r\n|\r|\n/', trim((string) $invoice->notes)) as $noteLine)
                <tr><td class="n">{{ ++$ln }}</td><td class="c"><span class="cm">&nbsp;&nbsp; {{ $noteLine }}</span></td></tr>
            @endforeach
            <tr><td class="n">{{ ++$ln }}</td><td class="c"><span class="cm">*/</span></td></tr>
        @endif
        @if(isset($bankAccounts) && $bankAccounts->isNotEmpty())
            <tr><td class="n">{{ ++$ln }}</td><td class="c">&nbsp;</td></tr>
            <tr><td class="n">{{ ++$ln }}</td><td class="c"><span class="vr">$uplata</span> <span class="pn">= [</span></td></tr>
            @foreach($bankAccounts as $account)
                <tr><td class="n">{{ ++$ln }}</td><td class="c i1"><span class="st">'{{ $account->bank_name }}'</span> <span class="pn">=&gt;</span> <span class="nu">{{ $account->account_number }}</span><span class="pn">,</span>@if($account->swift) <span class="cm">// SWIFT {{ $account->swift }}</span>@endif</td></tr>
            @endforeach
            <tr><td class="n">{{ ++$ln }}</td><td class="c"><span class="pn">];</span></td></tr>
        @endif
    </table>
</div>
</body>
</html>
