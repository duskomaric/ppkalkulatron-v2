@php
    $f = fn (int $v): string => number_format($v / 100, 2, ',', '.');
    $q = fn ($v): string => rtrim(rtrim(number_format((float) $v, 3, ',', '.'), '0'), ',');
    $c = $invoice->currencySymbol();
    $showVat = ($company->is_vat_obligor ?? true) || $invoice->tax_total > 0;
    $fiscal = $invoice->originalFiscalRecord();
    $spelled = \App\Support\SpelledAmount::of(intdiv($invoice->total, 100));
    $class = 'Racun'.preg_replace('/[^0-9]/', '', (string) $invoice->invoice_number);
@endphp
<!doctype html>
<html lang="sr">
<head>
    <meta charset="utf-8">
    <title>Račun {{ $invoice->invoice_number }}</title>
    <style>
        @page { margin: 0 }
        * { box-sizing: border-box }
        body { margin: 0; background: #f4f6fb; color: #1e293b; font-family: DejaVu Sans Mono, monospace; font-size: 7.5pt; line-height: 1.4 }
        .page { padding: 11mm 12mm 46mm }
        .attr { display: inline-block; padding: 1.3mm 3mm; background: #eceafe; border: 1px solid #d9d4fb; color: #4c3fd6; font-size: 6.8pt }
        .attr b { color: #7c3aed }
        .head { width: 100%; margin: 3mm 0 6mm; border-collapse: collapse }
        .head td { vertical-align: bottom }
        .kw { color: #7c3aed }
        .cls { color: #0e7490 }
        .head h1 { margin: 1.5mm 0 0; color: #14213d; font-family: DejaVu Sans, sans-serif; font-size: 21pt; letter-spacing: -0.6px }
        .head .sub { color: #64748b; font-size: 6.8pt }
        .badge { width: 46mm; padding: 2.6mm 3mm; background: #14213d; color: #fff; text-align: center; font-size: 6.6pt }
        .badge b { display: block; margin-top: 0.8mm; font-size: 9pt }
        .cards { width: 100%; border-collapse: separate; border-spacing: 4mm 0; margin: 0 -4mm }
        .cards td { padding: 0; vertical-align: top }
        .card { background: #fff; border: 1px solid #e1e7f2; border-top: 2px solid #4c3fd6 }
        .card .ct { padding: 2.2mm 3mm; background: #fafbfe; border-bottom: 1px solid #eaeff7 }
        .card .cb { padding: 3mm }
        .prop { padding: 0.7mm 0 }
        .pn { color: #94a3b8 }
        .pk { color: #0e7490 }
        .pv { color: #14213d; font-weight: bold }
        .ps { color: #15803d }
        .mute { color: #64748b }
        .sig { margin: 6mm 0 0; color: #64748b }
        .items { width: 100%; margin-top: 2.5mm; background: #fff; border: 1px solid #e1e7f2; border-collapse: collapse; table-layout: fixed }
        .items th { padding: 2.2mm 2mm; background: #14213d; color: #c7d2fe; text-align: left; font-size: 6.2pt; font-weight: normal; text-transform: uppercase; letter-spacing: 0.5px }
        .items td { padding: 2.6mm 2mm; border-bottom: 1px solid #eef2f8; vertical-align: top }
        .items tbody tr:last-child td { border-bottom: 0 }
        .idx { width: 8mm; color: #a5b4fc; text-align: center }
        .r { text-align: right; white-space: nowrap }
        .cn { text-align: center; white-space: nowrap }
        .nm { font-weight: bold }
        .ds { color: #64748b; font-size: 6.2pt }
        .close { color: #94a3b8; margin-top: 1.5mm }
        .foot { width: 100%; margin-top: 6mm; border-collapse: separate; border-spacing: 4mm 0 }
        .foot td { padding: 0; vertical-align: top }
        .foot .left { width: 54% }
        .foot .right { width: 46% }
        .ret { background: #fff; border: 1px solid #e1e7f2; border-left: 3px solid #4c3fd6 }
        .ret .rt { padding: 2.2mm 3mm; background: #fafbfe; border-bottom: 1px solid #eaeff7; color: #4c3fd6 }
        .ret .rb { padding: 3mm 3.5mm }
        .ret .line { padding: 1mm 0 }
        .ret .line span:last-child { float: right; font-weight: bold }
        .ret .all { margin-top: 2mm; padding: 2.6mm 3.5mm; background: #14213d; color: #fff; font-size: 10.5pt }
        .ret .all span:last-child { float: right; font-weight: bold }
        .note { background: #fff; border: 1px solid #e1e7f2; border-top: 2px solid #0e7490 }
        .note .ct { padding: 2.2mm 3mm; background: #fafbfe; border-bottom: 1px solid #eaeff7 }
        .note .nb { padding: 3mm }
        .note .nb.pre { white-space: pre-line }
        .note .attr.teal { background: #e6f6f9; border-color: #cfeaf0; color: #0e7490 }
        .small { margin-top: 4mm; color: #64748b; font-size: 6.4pt }
    </style>
</head>
<body>
<div class="page">
    <span class="attr"><b>#[</b>Racun(broj: '{{ $invoice->invoice_number }}', datum: '{{ $invoice->date?->format('d.m.Y.') }}')<b>]</b></span>

    <table class="head">
        <tr>
            <td>
                <div><span class="kw">final class</span> <span class="cls">{{ $class }}</span> <span class="kw">implements</span> <span class="cls">Faktura</span></div>
                <h1>Račun {{ $invoice->invoice_number }}</h1>
                <div class="sub">{{ $company->name }}@if($company->city) · {{ $company->city }}@endif</div>
            </td>
            <td class="badge">rok plaćanja<b>{{ $invoice->due_date?->format('d.m.Y.') }}</b>{{ $invoice->payment_type?->label() }} · {{ $invoice->currency }}</td>
        </tr>
    </table>

    <table class="cards">
        <colgroup><col style="width:50%"><col style="width:50%"></colgroup>
        <tr>
            <td>
                <div class="card">
                    <div class="ct"><span class="attr"><b>#[</b>Izdavalac<b>]</b></span></div>
                    <div class="cb">
                        <div class="prop"><span class="pk">naziv</span><span class="pn">:</span> <span class="pv">{{ $company->name }}</span></div>
                        @if($company->address)
                            <div class="prop"><span class="pk">adresa</span><span class="pn">:</span> <span class="ps">{{ $company->address }}</span></div>
                        @endif
                        @if($company->zip || $company->city)
                            <div class="prop"><span class="pk">mjesto</span><span class="pn">:</span> <span class="ps">{{ trim($company->zip.' '.$company->city) }}@if($company->country), {{ $company->country }}@endif</span></div>
                        @endif
                        @if($company->identification_number)
                            <div class="prop"><span class="pk">jib</span><span class="pn">:</span> <span class="pv">{{ $company->identification_number }}</span></div>
                        @endif
                        @if($company->vat_number)
                            <div class="prop"><span class="pk">pdv</span><span class="pn">:</span> <span class="pv">{{ $company->vat_number }}</span></div>
                        @endif
                        @if($company->phone || $company->email)
                            <div class="prop mute">{{ implode(' · ', array_filter([$company->phone, $company->email])) }}</div>
                        @endif
                    </div>
                </div>
            </td>
            <td>
                <div class="card">
                    <div class="ct"><span class="attr"><b>#[</b>Kupac<b>]</b></span></div>
                    <div class="cb">
                        <div class="prop"><span class="pk">naziv</span><span class="pn">:</span> <span class="pv">{{ $invoice->client?->name }}</span></div>
                        @if($invoice->client?->address)
                            <div class="prop"><span class="pk">adresa</span><span class="pn">:</span> <span class="ps">{{ $invoice->client->address }}</span></div>
                        @endif
                        @if($invoice->client?->zip || $invoice->client?->city)
                            <div class="prop"><span class="pk">mjesto</span><span class="pn">:</span> <span class="ps">{{ trim($invoice->client->zip.' '.$invoice->client->city) }}@if($invoice->client?->country), {{ $invoice->client->country }}@endif</span></div>
                        @endif
                        @if($invoice->client?->vat_id)
                            <div class="prop"><span class="pk">jib</span><span class="pn">:</span> <span class="pv">{{ $invoice->client->vat_id }}</span></div>
                        @endif
                        @if($invoice->client?->tax_id)
                            <div class="prop"><span class="pk">pdv</span><span class="pn">:</span> <span class="pv">{{ $invoice->client->tax_id }}</span></div>
                        @endif
                        @if($invoice->client?->phone)
                            <div class="prop mute">{{ $invoice->client->phone }}</div>
                        @endif
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <div class="sig">
        <span class="attr"><b>#[</b>Stavke(count: {{ $invoice->items->count() }})<b>]</b></span>
        <span class="mute">&nbsp; <span class="kw">public readonly array</span> $stavke;</span>
    </div>

    <table class="items">
        <thead>
        <tr>
            <th class="idx">#</th>
            <th style="width:{{ $showVat ? '34%' : '50%' }}">stavka</th>
            <th class="cn" style="width:8%">jm</th>
            <th class="r" style="width:9%">kol.</th>
            <th class="r" style="width:13%">cijena</th>
            @if($showVat)
                <th class="r" style="width:8%">pdv</th>
                <th class="r" style="width:12%">osnovica</th>
            @endif
            <th class="r" style="width:14%">iznos</th>
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
                <td class="idx">{{ $loop->iteration }}</td>
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

    <table class="foot">
        <tr>
            <td class="left">
                @if($invoice->notes)
                    <div class="note">
                        <div class="ct"><span class="attr teal"><b>#[</b>Napomena<b>]</b></span></div>
                        <div class="nb pre">{{ trim((string) $invoice->notes) }}</div>
                    </div>
                @endif
                @if(isset($bankAccounts) && $bankAccounts->isNotEmpty())
                    <div class="note" style="margin-top:4mm;border-top-color:#4c3fd6">
                        <div class="ct"><span class="attr"><b>#[</b>Uplata<b>]</b></span></div>
                        <div class="nb">@foreach($bankAccounts as $account)<div class="prop"><span class="pk">{{ $account->bank_name }}</span><span class="pn">:</span> <span class="pv">{{ $account->account_number }}</span>@if($account->swift) <span class="mute">SWIFT {{ $account->swift }}</span>@endif</div>@endforeach</div>
                    </div>
                @endif
            </td>
            <td class="right">
                <div class="ret">
                    <div class="rt"><span class="kw">return new</span> <span class="cls">Obracun</span>(</div>
                    <div class="rb">
                        <div class="line"><span><span class="pk">osnovica</span><span class="pn">:</span></span><span>{{ $f($invoice->subtotal) }} {{ $c }}</span></div>
                        @if($showVat)
                            <div class="line"><span><span class="pk">pdv</span><span class="pn">:</span></span><span>{{ $f($invoice->tax_total) }} {{ $c }}</span></div>
                        @endif
                        @if($fiscal?->fiscal_invoice_number)
                            <div class="line mute"><span><span class="pk">fiskalni</span><span class="pn">:</span></span><span>{{ $fiscal->fiscal_invoice_number }}</span></div>
                        @endif
                    </div>
                    <div class="all"><span>ukupno<span class="pn">:</span></span><span>{{ $f($invoice->total) }} {{ $c }}</span></div>
                </div>
                <div class="close">); <span class="mute">// slovima: {{ $spelled ?? number_format(intdiv($invoice->total, 100), 0, ',', '.') }} {{ $c }} i {{ str_pad($invoice->total % 100, 2, '0', STR_PAD_LEFT) }}/100</span></div>
            </td>
        </tr>
    </table>
</div>
</body>
</html>
