@extends('layouts.app')
@section('title', 'Pomoć')

@section('content')
    {{--
        Prati v1 help: uvod, sadržaj, pa sekcija po temi sa mjestom za slike ekrana.
        Sidra ovdje moraju pratiti #linkove iz podešavanja — inače vode u prazno.
    --}}
    <div class="space-y-8 max-w-3xl pb-10">
        <div class="p-5 rounded-3xl border border-[var(--color-border)] bg-[var(--color-surface)] relative overflow-hidden">
            <div class="absolute top-0 right-0 p-6 opacity-5">
                <x-icon name="info" class="h-16 w-16" />
            </div>
            <h2 class="text-xl font-black text-[var(--color-text-main)] tracking-tight italic relative z-10">
                Dobrodošli u ppKalkulatron
            </h2>
            <p class="text-sm text-[var(--color-text-muted)] mt-2 relative z-10">
                Aplikacija radi na uređaju: računi, klijenti i artikli su lokalni, a fiskalni uređaj
                se poziva direktno sa telefona. Ispod je redoslijed kojim se najlakše pokrenuti.
            </p>
        </div>

        <nav class="p-4 rounded-2xl border border-[var(--color-border)] bg-[var(--color-surface)]">
            <p class="text-[10px] font-black uppercase tracking-[0.2em] text-[var(--color-text-dim)] mb-3">Sadržaj</p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5">
                @foreach ([
                    'profil-kompanije' => 'Profil kompanije',
                    'bankovni-racuni' => 'Bankovni računi',
                    'valute' => 'Valute i kursevi',
                    'klijenti' => 'Klijenti',
                    'artikli' => 'Artikli',
                    'racuni' => 'Računi',
                    'fiskalizacija' => 'Fiskalizacija',
                    'skeniranje' => 'Skeniranje mreže',
                    'mail' => 'Mail (SMTP)',
                    'numeracija' => 'Numeracija dokumenata',
                    'stampa-racuna' => 'Štampa računa',
                    'napomene' => 'Napomene',
                    'meni' => 'Podešavanje menija',
                    'pin' => 'PIN i zaključavanje',
                ] as $anchor => $label)
                    <a href="#{{ $anchor }}"
                       class="text-xs font-bold text-[var(--color-text-muted)] hover:text-primary transition-colors px-2 py-1.5 rounded-lg hover:bg-[var(--color-surface-hover)]">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </nav>

        <x-help-section id="profil-kompanije" title="Profil kompanije" icon="building">
            <p>
                U <strong>Podešavanja → Profil kompanije</strong> unesite naziv, adresu, JIB i PDV broj.
                Ti podaci idu u zaglavlje svakog PDF-a, pa ih popunite prije prvog računa.
            </p>
            <p>
                <strong>PDV obveznik</strong> odlučuje da li se na računu prikazuju kolone poreza.
                <strong>Mali preduzetnik</strong> dodaje napomenu na dno dokumenta — tekst napomene
                možete promijeniti ispod prekidača.
            </p>

            <x-help-preview title="Profil kompanije" />
        </x-help-section>

        <x-help-section id="bankovni-racuni" title="Bankovni računi" icon="credit-card">
            <p>
                Račun ima <strong>naziv banke</strong>, <strong>broj računa</strong> i opciono
                <strong>SWIFT</strong> — SWIFT treba samo za uplate iz inostranstva.
            </p>
            <p>
                Prekidač <strong>Prikaži na dokumentima</strong> odlučuje hoće li se račun ispisati
                u instrukcijama za plaćanje na PDF-u. Možete držati više računa, a prikazivati samo neke.
            </p>

            <x-help-preview title="Bankovni računi" />
        </x-help-section>

        <x-help-section id="valute" title="Valute i kursevi" icon="hash">
            <p>
                Jedna valuta je <strong>osnovna</strong> i ona se ne briše. Za svaku drugu valutu
                unosite <strong>kurs prema KM</strong> sa datumom.
            </p>
            <p>
                Fiskalnom uređaju iznosi uvijek idu u KM. Za račun u stranoj valuti uzima se kurs
                na datum računa, ili posljednji raniji — bez kursa fiskalizacija se ne izvršava.
            </p>

            <x-help-preview title="Valute" />
        </x-help-section>

        <x-help-section id="klijenti" title="Klijenti" icon="contact">
            <p>
                <strong>JIB</strong> je identifikacija kupca i šalje se fiskalnom uređaju.
                <strong>PDV</strong> je poreski broj i služi samo za dokumente.
            </p>
            <p>
                Za veleprodaju je JIB kupca obavezan — bez njega uređaj ne evidentira promet.
                Za stranog kupca bez JIB-a šalje se <strong>VP:9999999999999</strong>.
            </p>

            <x-help-preview title="Klijenti" />
        </x-help-section>

        <x-help-section id="artikli" title="Artikli" icon="boxes">
            <p>
                Poreska oznaka određuje stopu koju uređaj primjenjuje. Koje oznake važe javlja sam
                uređaj — provjerite ih dugmetom <strong>Provjeri uređaj</strong> i nemojte ih pogađati.
            </p>
            <p>
                Zadnja cijena se pamti pri izdavanju računa i sljedeći put se ponudi sama.
            </p>

            <x-help-preview title="Artikli" />
        </x-help-section>

        <x-help-section id="racuni" title="Računi" icon="file-text">
            <p>
                Račun se sastoji od klijenta, stavki i načina plaćanja. Stavka se bira iz artikala —
                naziv, jedinica i poreska oznaka dolaze sa artikla.
            </p>
            <p>
                Cijena se unosi <strong>sa porezom</strong>; osnovica i porez se iz nje izvode, kako
                fiskalni uređaj i očekuje. Polje cijene se puni zdesna nalijevo, kao na kasi.
            </p>
            <p>
                Fiskalizovan račun se više ne može mijenjati ni brisati — ispravlja se stornom.
            </p>

            <x-help-preview title="Kreiranje računa" />
        </x-help-section>

        <x-help-section id="fiskalizacija" title="Fiskalizacija (OFS ESIR)" icon="file-text">
            <p>
                U <strong>Podešavanja → Fiskalizacija</strong> se povezuje OFS ESIR uređaj. Podržana su
                dva načina rada:
            </p>

            <ul class="list-disc pl-5 space-y-1">
                <li><strong>Cloud</strong> — uređaj preko pos.ofs.ba. Traži Base URL, API ključ, serijski broj i PAK.</li>
                <li><strong>Lokalni</strong> — uređaj na vašoj mreži. Traži Base URL (adresa uređaja) i API ključ.</li>
            </ul>

            <p>
                Poslije unosa provjerite vezu dugmetom <strong>Provjeri uređaj</strong>. Odgovor javlja
                UID uređaja i poreske oznake koje uređaj priznaje.
            </p>
            <p>
                Lokalni uređaj poslije uključivanja traži <strong>PIN sigurnosnog elementa</strong> i do
                tada ne fiskalizuje. PIN se unosi na istom ekranu, u zasebnoj sekciji.
            </p>
            <p>
                Ako veza pukne usred fiskalizacije, uređaj i dalje zna šta je snimio. U sekciji
                <strong>Potraga po RequestId</strong> unesite RequestId iz fiskalnog zapisa i provjerite
                je li račun prošao — prije nego što pokušate ponovo.
            </p>

            <x-help-preview title="Fiskalizacija" />
        </x-help-section>

        <x-help-section id="skeniranje" title="Skeniranje mreže" icon="search">
            <p>
                Ako ne znate adresu lokalnog uređaja, aplikacija je nađe sama. Dugme
                <strong>Skeniraj mrežu</strong> čita opseg sa mrežnog interfejsa telefona i provjerava
                sve adrese na portu <strong>{{ \App\Services\NetworkScanner::PORT }}</strong>.
            </p>
            <p>
                Pretraga cijele podmreže traje oko sekundu. Pronađen uređaj samo dodirnete i adresa
                se upiše u Base URL. Ako je kasa na drugoj podmreži, opseg možete unijeti ručno —
                na primjer <strong>192.168.31.100-105</strong> ili <strong>192.168.31.</strong>
            </p>

            <x-help-preview title="Skeniranje mreže" />
        </x-help-section>

        <x-help-section id="mail" title="Mail (SMTP)" icon="mail">
            <p>
                Da bi računi išli sa vaše adrese, u <strong>Podešavanja → Mail</strong> unesite adresu
                i ime pošiljaoca, host, port, korisničko ime i lozinku. Bez podešenog SMTP-a koristi se
                sistemska konfiguracija.
            </p>
            <p>
                Većina provajdera ne prima običnu lozinku naloga nego <strong>app lozinku</strong>.
                Uputstva su ispod.
            </p>

            <div class="space-y-2" x-data="{ open: null }">
                @foreach ([
                    ['gmail', 'Gmail', 'smtp.gmail.com', [
                        'Prijavite se na Google Account i otvorite sekciju Security.',
                        'Uključite 2-Step Verification ako već nije uključena.',
                        'Otvorite myaccount.google.com/apppasswords.',
                        'U polje App name upišite ppKalkulatron i kliknite Create.',
                        'Dobijenih 16 znakova unesite kao SMTP lozinku, bez razmaka.',
                    ]],
                    ['outlook', 'Outlook / Office365', 'smtp.office365.com', [
                        'Prijavite se na Microsoft Security.',
                        'Odaberite Advanced security options.',
                        'U sekciji App passwords kliknite Create a new app password.',
                        'Kopirajte lozinku u polje SMTP lozinka.',
                    ]],
                    ['yahoo', 'Yahoo', 'smtp.mail.yahoo.com', [
                        'Otvorite Account Security u postavkama Yahoo naloga.',
                        'Kliknite Generate app password.',
                        'Odaberite Other App i upišite ppKalkulatron.',
                        'Kopirajte dobijenu lozinku u aplikaciju.',
                    ]],
                    ['icloud', 'iCloud', 'smtp.mail.me.com', [
                        'Prijavite se na appleid.apple.com.',
                        'U sekciji Sign-In and Security odaberite App-Specific Passwords.',
                        'Kliknite Generate an app-specific password i unesite ppKalkulatron.',
                        'Kopirajte lozinku u polje SMTP lozinka.',
                    ]],
                ] as [$key, $label, $host, $steps])
                    <div class="rounded-xl border border-[var(--color-border)] overflow-hidden">
                        <button type="button" x-on:click="open = open === '{{ $key }}' ? null : '{{ $key }}'"
                                class="w-full flex items-center justify-between p-4 bg-[var(--color-bg)]/40 hover:bg-[var(--color-surface-hover)] transition-colors text-left cursor-pointer">
                            <span class="text-sm font-bold text-[var(--color-text-main)]">{{ $label }}</span>
                            <x-icon name="chevron-down" class="h-4 w-4 text-[var(--color-text-dim)] transition-transform"
                                    ::class="open === '{{ $key }}' && 'rotate-180'" />
                        </button>

                        <div x-show="open === '{{ $key }}'" x-cloak class="p-4 space-y-2 border-t border-[var(--color-border)]">
                            <ol class="list-decimal pl-5 space-y-1">
                                @foreach ($steps as $step)
                                    <li>{{ $step }}</li>
                                @endforeach
                            </ol>

                            <p class="text-[11px] font-bold text-[var(--color-text-dim)]">
                                Parametri: Host {{ $host }} · Port 587 · Enkripcija TLS
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>

            <x-help-preview title="Mail" />
        </x-help-section>

        <x-help-section id="numeracija" title="Numeracija dokumenata" icon="hash">
            <p>
                Broj se dodjeljuje sam, po redu, u obliku
                <strong>0001/{{ date('Y') }}</strong>. Broj nula i prefiks se podešavaju u
                <strong>Podešavanja → Generalno</strong>.
            </p>
            <p>
                Ako obrišete zadnji račun, taj broj se oslobađa i sljedeći ga dobija. Uz uključen
                <strong>reset po godini</strong> brojanje kreće ispočetka svakog januara.
            </p>

            <x-help-preview title="Numeracija" />
        </x-help-section>

        <x-help-section id="stampa-racuna" title="Štampa računa" icon="file-text">
            <p>
                <strong>Izgled računa</strong> bira između isječka (Slip) i punog računa (Invoice).
                <strong>Format slike</strong> je PNG, PDF ili HTML — uz Invoice raspored PNG nije
                dostupan jer ga uređaj ne iscrtava.
            </p>
            <p>
                <strong>Štampaj račun</strong> šalje nalog za štampu samom uređaju. Slika računa se
                čuva uz fiskalni zapis i može se poslati mailom kao prilog.
            </p>

            <x-help-preview title="Štampa računa" />
        </x-help-section>

        <x-help-section id="napomene" title="Napomene" icon="sticky-note">
            <p>
                Podrazumijevana napomena iz <strong>Podešavanja → Generalno</strong> upisuje se u svaki
                novi račun i tu se može izmijeniti. Novi red se poštuje i na PDF-u.
            </p>

            <x-help-preview title="Napomene" />
        </x-help-section>

        <x-help-section id="meni" title="Podešavanje menija" icon="cog">
            <p>
                U <strong>Podešavanja → Vizuelna podešavanja</strong> birate koji moduli stoje u donjem
                meniju. Ostali se otvaraju iz podešavanja — ništa se ne sakriva, samo se premješta.
            </p>

            <x-help-preview title="Podešavanje menija" />
        </x-help-section>

        <x-help-section id="pin" title="PIN i zaključavanje" icon="lock">
            <p>
                PIN je opcionalan i ima tačno četiri cifre. Dok nije postavljen, aplikacija se otvara
                odmah. Kad ga postavite, traži se pri svakom pokretanju.
            </p>
            <p>
                <strong>Automatsko zaključavanje</strong> zaključa aplikaciju kad se ostavi otvorena, i
                kad se telefon zaključa pa vrati. Vrijeme birate sami; „Nikad" isključuje tu provjeru.
            </p>

            <x-help-preview title="PIN" />
        </x-help-section>
    </div>
@endsection
