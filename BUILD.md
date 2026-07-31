# Pokretanje i pakovanje

## Šta je moguće, a šta nije

| Cilj | Paket | Status |
|---|---|---|
| macOS aplikacija (.app / .dmg) | `nativephp/electron` | **Nije moguće danas.** Paket podržava Laravel do 12, projekat je na 13 — Composer ga odbija. |
| iPhone / iPad | `nativephp/mobile` | Moguće, traži **Xcode** (nije instaliran) |
| Android APK | `nativephp/mobile` | **Radi.** JDK 21 i Android SDK su instalirani (bez Android Studija) |
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

Android Studio nije potreban. Dovoljni su JDK i SDK command-line tools, oboje bez
`sudo`:

```bash
brew install openjdk@21

mkdir -p ~/Library/Android/sdk/cmdline-tools
curl -L -o /tmp/cmdtools.zip https://dl.google.com/android/repository/commandlinetools-mac-13114758_latest.zip
unzip -q /tmp/cmdtools.zip -d /tmp/cmdtools
mv /tmp/cmdtools/cmdline-tools ~/Library/Android/sdk/cmdline-tools/latest

export JAVA_HOME=/opt/homebrew/opt/openjdk@21
export ANDROID_HOME=$HOME/Library/Android/sdk
export PATH="$JAVA_HOME/bin:$ANDROID_HOME/platform-tools:$PATH"

yes | $ANDROID_HOME/cmdline-tools/latest/bin/sdkmanager --licenses
$ANDROID_HOME/cmdline-tools/latest/bin/sdkmanager \
    "platform-tools" "platforms;android-36" "build-tools;36.0.0"
```

Gradle se ne instalira posebno — NativePHP projekat nosi svoj wrapper (`./gradlew`).
U `.env` ide samo:

```
NATIVEPHP_ANDROID_SDK_LOCATION="/Users/<ti>/Library/Android/sdk"
```

### Potpisni ključ

Pravi se jednom i **ne ide u git**. Bez njega se aplikacija u Play Store-u ne može
ažurirati — ako se izgubi, mora nova aplikacija pod drugim imenom paketa.

```bash
keytool -genkeypair -v -keystore ~/.ppkalkulatron/release.keystore \
    -alias ppkalkulatron -keyalg RSA -keysize 2048 -validity 10000 \
    -dname "CN=PlusPlus IT, O=PlusPlus IT d.o.o., L=Banja Luka, C=BA"
```

### APK

```bash
npm run build     # obavezno, inače se pakuju stari CSS i JS

php artisan native:package android --build-type=release \
    --keystore="$HOME/.ppkalkulatron/release.keystore" \
    --keystore-password=... --key-alias=ppkalkulatron --key-password=... \
    --output="$PWD/dist"
```

`native:package` pripremi sve i na kraju pozove Gradle. Ako se javi
**„TTY mode requires /dev/tty to be read/writable"** — komanda je pokrenuta bez
terminala. Projekat je tada već spreman, pa se Gradle pozove ručno:

```bash
cd nativephp/android
./gradlew assembleRelease --no-daemon \
    -PMYAPP_UPLOAD_STORE_FILE="$HOME/.ppkalkulatron/release.keystore" \
    -PMYAPP_UPLOAD_KEY_ALIAS=ppkalkulatron \
    -PMYAPP_UPLOAD_STORE_PASSWORD=... \
    -PMYAPP_UPLOAD_KEY_PASSWORD=...
```

APK završi u `nativephp/android/app/build/outputs/apk/release/app-release.apk`.

Prvi build traje dugo (~20 min): Gradle povuče sebe, NDK i CMake, oko 2 GB. Svaki
sljedeći je pitanje minuta. Provjera šta je stvarno izašlo:

```bash
$ANDROID_HOME/build-tools/36.0.0/aapt2 dump badging <apk> | head -3
$ANDROID_HOME/build-tools/36.0.0/apksigner verify --print-certs <apk>
```

`--build-type=bundle` pravi AAB, što Play Store traži; za slanje pojedincu je APK
jednostavniji jer se instalira direktno.

### Emulator i priključen telefon

`php artisan native:run android` gradi **i pokreće** aplikaciju, pa traži uređaj —
bez emulatora ili priključenog telefona samo čeka. Za emulator treba još
`sdkmanager "system-images;android-36;google_apis;arm64-v8a"` i `avdmanager create avd`.
Za samo APK koristi `native:package`.

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

Pošalji APK iz `nativephp/android/app/build/outputs/apk/release/` kao običnu
datoteku — mailom, preko WeTransfera, Drive-a, kako god. Tester ga otvori na
telefonu i dopusti instalaciju iz nepoznatih izvora kad Android pita.

APK je potpisan sopstvenim ključem, ne Play Store-ovim, pa Play Protect prikaže
upozorenje „nepoznat programer" — na njemu se klikne *Ipak instaliraj*. To je
očekivano za testni build.

Uredniji put za više testera je Firebase App Distribution (besplatno, tester dobija
mail sa linkom) ili interna testna grupa u Play Console-u, koja traži AAB
(`--build-type=bundle`) i jednokratnih 25 USD za nalog.

## Kad se pojavi macOS

`nativephp/electron` čeka podršku za Laravel 13. Kad izađe:

```bash
composer require nativephp/electron
php artisan native:install
php artisan native:serve      # razvoj
php artisan native:build      # .app i .dmg
```

Do tada se za Mac koristi preglednik uz `php artisan serve`.

## Identitet aplikacije

Ikona i splash ekrani se crtaju iz boja aplikacije, ne stoje kao neobjašnjivi
binarni fajlovi:

```bash
php artisan app:brand-assets
```

Napravi `public/icon.png` (1024×1024), `public/splash.png` i
`public/splash-dark.png` (1080×1920) — putanje koje NativePHP očekuje i sam
skalira za sve gustine ekrana. Traži GD ekstenziju.

Ostalo je u `config/nativephp.php` i `.env`:

| Šta | Gdje | Vrijednost |
|---|---|---|
| Ime aplikacije | `.env` `APP_NAME` | ppKalkulatron |
| Bundle ID | `.env` `NATIVEPHP_APP_ID` | com.plusplusit.ppkalkulatron |
| Verzija | `.env` `NATIVEPHP_APP_VERSION` / `_CODE` | 0.1.0 / 1 |
| Boja teme (Android) | `nativephp.android.theme.color_primary` | `#F59E0B` (amber 500) |
| Orijentacija | `nativephp.orientation` | samo portret |
| iPad | `nativephp.ipad` | isključen — jednom uključen u App Store-u ne može se povući |
| Izgled sistema | `nativephp.appearance` | `system` |

Tema u aplikaciji (svijetla/tamna/sistemska) se bira u profilu i pamti u
pregledniku. `appearance` pokriva samo sistemske trake i tastaturu, i ne prati taj
izbor — ako korisnik u aplikaciji izabere svijetlu a telefon je taman, sistemske
trake ostaju tamne.

## Firebase

`google-services.json` stoji u `firebase/`, **ne** u korijenu — vidi
[firebase/README.md](firebase/README.md). U korijenu bi ga NativePHP kopirao u
Android projekat i build bi pao na `Plugin with id 'com.google.gms.google-services'
not found`, jer taj Gradle plugin dolazi tek sa `nativephp/mobile-firebase`.

Za App Distribution (slanje APK-a testerima) Firebase u aplikaciji uopšte ne treba.

## Zamke koje su već pregažene

- **`storage/framework` ne ide u paket.** NativePHP ga izostavlja, a `config/view.php`
  traži `realpath()` te putanje — pakovanje je padalo na „Please provide a valid cache
  path". `bootstrap/app.php` sada napravi te foldere prije nego što se aplikacija podigne.
- **`npm run build` u Sail kontejneru ne radi**: `node_modules` su instalirani na
  hostu pa rolldown nema `linux-arm64` binding. Gradi se na hostu.
- **PHP na uređaju je bez ICU-a** (`nativephp.lock` → `php.icu: false`). Iznos
  slovima na PDF-u se zato računa u `App\Support\SpelledAmount`, ne kroz
  `NumberFormatter` — inače bi telefon ispisao „330" umjesto „trista trideset".
- **`APP_DEBUG` i `APP_ENV` se brišu iz paketa** (`cleanup_env_keys`), pa upakovana
  aplikacija uzima podrazumijevano `production` i `debug=false`. Bez toga bi
  korisnik na telefonu vidio Laravel stack trace.
- **Verzija** se diže u `.env` (`NATIVEPHP_APP_VERSION`, `NATIVEPHP_APP_VERSION_CODE`)
  ili komandom `native:release`. Play Store odbija isti `version_code` dva puta.
