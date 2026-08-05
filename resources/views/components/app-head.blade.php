@props(['title' => null])

{{-- Zaglavlje dokumenta: ikone, tema i boje identiteta, isto za aplikaciju i ekran za otključavanje. --}}
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="{{ \App\Support\Brand::background('dark') }}">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ $title ?: config('app.name') }}</title>

<link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
<link rel="icon" type="image/png" href="{{ asset('icon.png') }}">
<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

{{-- Tema se primjenjuje prije iscrtavanja, inače tamna bljesne pri svijetloj temi. --}}
<script>
    (() => {
        const choice = localStorage.getItem('theme') || 'dark';
        const dark = choice === 'system'
            ? window.matchMedia('(prefers-color-scheme: dark)').matches
            : choice === 'dark';
        document.documentElement.classList.toggle('dark', dark);
        document.documentElement.classList.toggle('light', ! dark);

        // Tema se bira u aplikaciji, ne u sistemu, pa i sistemska traka mora za njom.
        document.querySelector('meta[name=theme-color]')?.setAttribute(
            'content',
            dark ? @js(\App\Support\Brand::background('dark')) : @js(\App\Support\Brand::background('light')),
        );
    })();
</script>

@vite(['resources/css/app.css', 'resources/js/app.js'])

{{-- Glavna boja se bira u podešavanjima, pa se ubacuje ovdje umjesto da stoji u CSS-u.
     Dvostruki `:root` diže specifičnost: u dev režimu Vite ubaci svoj CSS poslije ovoga. --}}
<style>:root:root { --primary-base: {{ \App\Support\Brand::css() }}; }</style>
