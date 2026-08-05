<?php

/**
 * Poruke validacije na srpskom (latinica, ijekavica).
 *
 * Ovdje su samo pravila koja aplikacija zaista koristi — Laravel za sve ostalo
 * pada nazad na engleski, pa nema smisla prevoditi ono što se nikad ne pojavi.
 */
return [
    'accepted' => 'Polje :attribute mora biti prihvaćeno.',
    'after' => 'Polje :attribute mora biti datum poslije :date.',
    'after_or_equal' => 'Polje :attribute ne može biti prije :date.',
    'array' => 'Polje :attribute mora biti lista.',
    'before' => 'Polje :attribute mora biti datum prije :date.',
    'boolean' => 'Polje :attribute mora biti da ili ne.',
    'confirmed' => 'Potvrda za :attribute se ne poklapa.',
    'date' => 'Polje :attribute mora biti ispravan datum.',
    'digits' => 'Polje :attribute mora imati :digits cifara.',
    'digits_between' => 'Polje :attribute mora imati između :min i :max cifara.',
    'email' => 'Polje :attribute mora biti ispravna email adresa.',
    'enum' => 'Izabrana vrijednost za :attribute nije dozvoljena.',
    'exists' => 'Izabrana vrijednost za :attribute ne postoji.',
    'file' => 'Polje :attribute mora biti datoteka.',
    'image' => 'Polje :attribute mora biti slika.',
    'in' => 'Izabrana vrijednost za :attribute nije dozvoljena.',
    'integer' => 'Polje :attribute mora biti cijeli broj.',
    'ip' => 'Polje :attribute mora biti ispravna IP adresa.',
    'max' => [
        'array' => 'Polje :attribute ne smije imati više od :max stavki.',
        'file' => 'Polje :attribute ne smije biti veće od :max kilobajta.',
        'numeric' => 'Polje :attribute ne smije biti veće od :max.',
        'string' => 'Polje :attribute ne smije imati više od :max znakova.',
    ],
    'min' => [
        'array' => 'Polje :attribute mora imati bar :min stavki.',
        'file' => 'Polje :attribute mora biti bar :min kilobajta.',
        'numeric' => 'Polje :attribute ne smije biti manje od :min.',
        'string' => 'Polje :attribute mora imati bar :min znakova.',
    ],
    'numeric' => 'Polje :attribute mora biti broj.',
    'regex' => 'Format polja :attribute nije ispravan.',
    'required' => 'Polje :attribute je obavezno.',
    'required_if' => 'Polje :attribute je obavezno kada je :other jednako :value.',
    'same' => 'Polja :attribute i :other moraju biti ista.',
    'size' => [
        'array' => 'Polje :attribute mora imati :size stavki.',
        'file' => 'Polje :attribute mora biti :size kilobajta.',
        'numeric' => 'Polje :attribute mora biti :size.',
        'string' => 'Polje :attribute mora imati :size znakova.',
    ],
    'string' => 'Polje :attribute mora biti tekst.',
    'uploaded' => 'Polje :attribute nije prenijeto do kraja — datoteka je veća od dozvoljene.',
    'unique' => 'Vrijednost polja :attribute je već zauzeta.',
    'url' => 'Polje :attribute mora biti ispravan URL.',

    'custom' => [],
    'attributes' => [],
];
