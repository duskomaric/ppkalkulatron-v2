# ppKalkulatron v2

Laravel + NativePHP for Mobile. Prepisujemo v1 (`../ppKalkulatron`) feature po feature —
v1 ostaje referenca. Popis je u [FEATURES.md](FEATURES.md).

**Zašto v2:** fiskalna kasa je zaseban uređaj na LAN-u i govori samo HTTP. Preglednik
to blokira sa HTTPS stranice (mixed content), a HTTP stranica na javnom serveru je
blokirana od Chrome 94 (Private Network Access). U NativePHP-u PHP radi **na uređaju**,
pa poziv prema kasi ide iz PHP-a i nijedno od tih ograničenja ne važi — `OFSService`
iz v1 se koristi kakav je.

## Šta je već postavljeno

| | Verzija |
|---|---|
| Laravel | 13.23 |
| nativephp/mobile | 4.0.0 (MIT, besplatan od v3) |
| PHP na uređaju | 8.4.23 |
| Sail | mysql + redis |
| App ID | `com.plusplusit.ppkalkulatron` |

`native:install` je prošao — Android projekt kreiran, PHP binari za Android skinuti.
Folder `nativephp/` je generisan i sam sebe ignoriše u gitu (paket tamo upiše
`.gitignore` sa `*`), pa 70 MB artefakata ne ide u repo.

## Lokalni razvoj

```bash
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate
```

Sail pokriva web/dev stranu. **Native build ne ide kroz Sail** — vidi ispod.

## Prije prvog builda — treba na hostu

`native:run` i `native:build` pozivaju Gradle i Xcode, pa moraju na macOS hostu,
ne u kontejneru:

```bash
brew install php composer
```

Plus Android Studio (Android SDK) i Xcode za iOS.

`native:install` je ovdje pušten u Linux kontejneru, pa je **kreiran samo Android
projekt**. Za iOS ga treba ponovo pustiti na macOS-u:

```bash
php artisan native:install
```

Korisne komande: `native:debug` (provjera okoline), `native:run`, `native:emulator`,
`native:tail` (Laravel logovi sa telefona), `native:watch`.

## Šta je urađeno

- **Zaključavanje PIN-om** — opcionalno. Prvi put nije podešeno i ulazi se direktno
  u račune; kad se postavi u podešavanjima, traži se pri pokretanju. Bez brojača
  pokušaja i bez traženja trenutnog PIN-a. Čuva se kao hash.
- **Računi** — lista (kartice na telefonu, mreža na desktopu), forma sa stavkama,
  detalj, izmjena i brisanje. Numeracija se izvodi iz samih računa, pa brisanje
  oslobađa broj bez ijedne dodatne linije — u v1 to nije radilo.
- **UI prati v1** — isti tokeni (amber, tamna tema, svjetleći krugovi), Tailwind 4.
- **OFSService** prenesen iz v1, gleda na cloud kasu. `php artisan ofs:ping`.

Komponente su namjerno veće cjeline: `x-invoices.list` je cijela lista,
`x-invoices.form` cijela forma. Dijelimo ih kasnije po potrebi.

### Demo podaci

`sail artisan migrate:fresh --seed` napravi tri klijenta, četiri artikla i četiri
računa. PIN se postavlja iz tinkera:

```bash
sail artisan tinker --execute="app(\App\Services\PinLock::class)->set('1111');"
```

## Zatečene stvari koje utiču na dizajn

**ICU je isključen u PHP-u na uređaju.** `nativephp.lock` kaže `"icu": false`.
v1 koristi `NumberFormatter::SPELLOUT` za "Slovima:" na PDF-ovima — to na uređaju
neće raditi. Treba vlastita implementacija ili render PDF-a na serveru.

**Numeracija dokumenata offline.** v1 izvodi sljedeći broj iz dokumenata u centralnoj
bazi. Dva uređaja offline izvela bi isti broj, a to je fiskalni dokument. Odlučiti
prije koda: opseg po uređaju, prefiks po uređaju, ili numeracija samo online.

**Sinhronizacija.** Šta je izvor istine — uređaj ili server? Šta kad se razlikuju?

## Git remote

Lokalni repo je spreman, remote treba kreirati (`gh` nije instaliran na ovoj mašini):

```bash
git remote add origin git@github.com:duskomaric/pp-kalkulatron-v2.git
git push -u origin main
```
