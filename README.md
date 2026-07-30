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

## Spike — pripremljen, čeka toolchain

`OFSService` je prenesen iz v1 i **radi** — provjereno protiv prave cloud kase
(`uid 5BW66JRX`, oznake F/N/P/E/T/A/B/C) i protiv lažnog ESIR-a.

```bash
php artisan ofs:ping --url=http://192.168.31.102:3566 --key=KLJUC
```

Blade ekran za telefon je na `/spike` — polje za Base URL i dugme koje zove
`/api/attention`, pa se rezultat vidi na uređaju.

**Lažni ESIR** za kad prava kasa nije na mreži — isti port, iste putanje, vraća
i primljene headere da se vidi da su stigli:

```bash
php -S 0.0.0.0:3566 tools/mock-esir.php
```

Sa telefona onda: `http://<IP-racunara>:3566`.

### Ostaje da se dokaže

`native:run` na pravom Android telefonu — jedino to potvrđuje da OS pušta plain
HTTP na LAN iz native aplikacije. Za to treba:

```bash
brew install --cask android-studio
```

Android prvo — nema review-a ni pretplate. Zatim iOS, gdje dodatno treba ATS i
dozvola za lokalnu mrežu.

Nakon toga isto na iOS-u, gdje dodatno treba:

- `NSAllowsLocalNetworking` u ATS-u
- `NSLocalNetworkUsageDescription` — iOS 14+ traži dozvolu korisnika za lokalnu mrežu

Nijedno od toga nije potvrđeno u dokumentaciji koju sam našao; iOS je zato drugi korak.

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
