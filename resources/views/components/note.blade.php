@props(['variant' => 'info'])

{{--
    Napomena uz polje ili sekciju.

    „info" nosi boju aplikacije i koristi se za objašnjenja i stanja koja korisnik
    treba da primijeti; „warning" je žuta i ostaje za upozorenja gdje se nešto može
    izgubiti ili prepisati.
--}}
@php
    $tones = [
        'info' => 'border-primary/30 bg-primary/10 text-primary',
        'warning' => 'border-amber-500/30 bg-amber-500/10 text-amber-700 dark:text-amber-300',
    ];
@endphp

<p {{ $attributes->class('rounded-xl border px-3 py-2 text-xs font-bold leading-relaxed '.$tones[$variant]) }}>
    {{ $slot }}
</p>
