<?php

use App\Support\SpelledAmount;

it('ispisuje iznos slovima', function (int $number, string $expected) {
    expect(SpelledAmount::of($number))->toBe($expected);
})->with([
    [0, 'nula'],
    [1, 'jedan'],
    [7, 'sedam'],
    [11, 'jedanaest'],
    [21, 'dvadeset jedan'],
    [45, 'četrdeset pet'],
    [100, 'sto'],
    [330, 'trista trideset'],
    [999, 'devetsto devedeset devet'],
    [1000, 'hiljadu'],
    [1001, 'hiljadu jedan'],
    [2000, 'dvije hiljade'],
    [5000, 'pet hiljada'],
    [2170, 'dvije hiljade sto sedamdeset'],
    [21000, 'dvadeset jedna hiljada'],
    [100000, 'sto hiljada'],
    [1000000, 'jedan milion'],
    [2500000, 'dva miliona petsto hiljada'],
]);

it('nosi znak minus', function () {
    expect(SpelledAmount::of(-42))->toBe('minus četrdeset dva');
});
