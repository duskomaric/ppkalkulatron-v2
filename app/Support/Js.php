<?php

namespace App\Support;

use Illuminate\Support\Js as LaravelJs;

/**
 * Gradnja Alpine izraza iz PHP-a.
 *
 * Treba jer se @js() i {{ }} ne kompajliraju unutar atributa Blade komponente —
 * vrijednost tamo stiže doslovno. Vezani atribut (:x-on:click) se evaluira kao
 * PHP, pa poziv sklapamo ovdje sa ispravno kodiranim argumentima.
 */
class Js
{
    public static function call(string $function, mixed ...$arguments): string
    {
        $encoded = array_map(fn ($argument) => (string) LaravelJs::from($argument), $arguments);

        return $function.'('.implode(', ', $encoded).')';
    }
}
