# Pokretanje i pakovanje

## Šta je moguće, a šta nije

| Cilj | Paket | Status |
|---|---|---|
| macOS aplikacija (.app / .dmg) | `nativephp/electron` | **Nije moguće danas.** Paket podržava Laravel do 12, projekat je na 13 — Composer ga odbija. |
| iPhone / iPad | `nativephp/mobile` | Moguće, traži **Xcode** (nije instaliran) |
| Android | `nativephp/mobile` | Moguće, traži **Android Studio, Java, Gradle** (nije instalirano) |
| Bilo koji preglednik, na mreži | ništa | Radi odmah — vidi *Slanje na test* |

`php artisan native:debug` u svakom trenutku ispisuje šta je od alata prisutno.

## Lokalno pokretanje

Baza je SQLite i za razvoj i za upakovanu aplikaciju — na uređaju MySQL servera nema,
a razlike između dva sistema baza su već napravile dvije greške (`YEAR()` i
`updateOrCreate` po datumu). Zato je isti sistem svuda.

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
npm run build
php artisan serve
```

PIN se postavlja u podešavanjima; za probu:

```bash
php artisan tinker --execute='app(\App\Services\PinLock::class)->set("1111");'
```

Sail se može koristiti (`./vendor/bin/sail up -d`), ali nije potreban — PHP na hostu
je dovoljan i to je isti put kojim ide i pakovanje.

## iPhone / iPad

Traži Xcode iz App Store-a, pa jednom:

```bash
sudo xcode-select -s /Applications/Xcode.app/Contents/Developer
sudo xcodebuild -license accept
brew install cocoapods
```

Zatim:

```bash
npm run build                              # bez ovoga se pakuju stari CSS i JS
php artisan native:run --target=ios        # build i pokretanje u simulatoru
php artisan native:build --target=ios --release   # za pravi uređaj i App Store
```

Za pravi uređaj treba Apple Developer nalog (99 USD/god) i `NATIVEPHP_DEVELOPMENT_TEAM`
u `.env` — Team ID iz developer.apple.com. `php artisan native:credentials` vodi kroz
potpisivanje, a `native:open` otvori Xcode projekat ako treba ručno dirati.

## Android

Traži Android Studio (donosi SDK i Gradle) i Java 17+. Poslije instalacije u `.env`:

```
NATIVEPHP_ANDROID_SDK_LOCATION=/Users/<ti>/Library/Android/sdk
NATIVEPHP_GRADLE_PATH=/Applications/Android Studio.app/Contents/gradle/gradle-8.x/bin/gradle
```

Zatim:

```bash
npm run build
php artisan native:run --target=android           # emulator ili priključen telefon
php artisan native:build --target=android --release
php artisan native:package                        # potpisani APK/AAB za distribuciju
```

Potpisivanje traži keystore; `native:credentials` ga napravi. **Keystore i njegove
lozinke ne idu u git** — bez njih se izlazi iz Play Store-a ne mogu ažurirati.

## Slanje na test

### Odmah, bez ikakvih alata

Aplikacija je Laravel projekat, pa se može pokazati preko mreže:

```bash
php artisan serve --host=0.0.0.0 --port=8080
```

Ko je na istoj Wi-Fi mreži otvori `http://<tvoja-ip>:8080`. Za nekoga izvan mreže
posluži tunel (`cloudflared tunnel --url http://localhost:8080` ili `ngrok http 8080`).

Dvije stvari treba znati: baza je jedna zajednička, dakle svi rade nad istim podacima;
i fiskalni uređaj se poziva sa **servera**, ne sa telefona testera — lokalna kasa radi
samo ako je server na istoj mreži kao kasa.

### iPhone, pravi uređaj

TestFlight. `native:build --target=ios --release`, pa `--upload-to-app-store`, i
testeri dobijaju pozivnicu mailom. Traži Apple Developer nalog.

### Android, pravi uređaj

Najlakše: `native:build --target=android --release` napravi APK koji se pošalje kao
datoteka; tester u podešavanjima dopusti instalaciju iz nepoznatih izvora. Uredniji
put je Firebase App Distribution ili Play Console interna testna grupa (AAB).

## Kad se pojavi macOS

`nativephp/electron` čeka podršku za Laravel 13. Kad izađe:

```bash
composer require nativephp/electron
php artisan native:install
php artisan native:serve      # razvoj
php artisan native:build      # .app i .dmg
```

Do tada se za Mac koristi preglednik uz `php artisan serve`.

## Zamke koje su već pregažene

- **`storage/framework` ne ide u paket.** NativePHP ga izostavlja, a `config/view.php`
  traži `realpath()` te putanje — pakovanje je padalo na „Please provide a valid cache
  path". `bootstrap/app.php` sada napravi te foldere prije nego što se aplikacija podigne.
- **`npm run build` u Sail kontejneru ne radi**: `node_modules` su instalirani na
  hostu pa rolldown nema `linux-arm64` binding. Gradi se na hostu.
- **Verzija** se diže u `.env` (`NATIVEPHP_APP_VERSION`, `NATIVEPHP_APP_VERSION_CODE`)
  ili komandom `native:release`. Play Store odbija isti `version_code` dva puta.
