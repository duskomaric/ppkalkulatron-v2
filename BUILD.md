# Build i distribucija

| Cilj | Status | Šta treba |
|---|---|---|
| **Android APK** | radi | JDK 21 + Android SDK (već instalirani) |
| **iPhone** | ne može odavde | [Xcode](https://apps.apple.com/app/xcode/id497799835) nije instaliran |
| **macOS** | ne može | [`nativephp/electron`](https://nativephp.com/docs/desktop) podržava Laravel ≤ 12, ovo je 13 |
| **Preglednik, preko mreže** | radi odmah | ništa |

`php artisan native:debug` ispiše šta je od alata prisutno.

## Razvoj

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
touch database/database.sqlite && php artisan migrate --seed
npm run build && php artisan serve
```

SQLite je baza i u razvoju i u aplikaciji — na uređaju MySQL servera nema.
PIN za probu: `php artisan tinker --execute='app(\App\Services\PinLock::class)->set("1111");'`

## Android

Jednom, bez `sudo` i bez Android Studija:

```bash
brew install openjdk@21

mkdir -p ~/Library/Android/sdk/cmdline-tools
curl -L -o /tmp/cmd.zip https://dl.google.com/android/repository/commandlinetools-mac-13114758_latest.zip
unzip -q /tmp/cmd.zip -d /tmp/cmd && mv /tmp/cmd/cmdline-tools ~/Library/Android/sdk/cmdline-tools/latest

export JAVA_HOME=/opt/homebrew/opt/openjdk@21
export ANDROID_HOME=$HOME/Library/Android/sdk
export PATH="$JAVA_HOME/bin:$ANDROID_HOME/platform-tools:$PATH"

yes | $ANDROID_HOME/cmdline-tools/latest/bin/sdkmanager --licenses
$ANDROID_HOME/cmdline-tools/latest/bin/sdkmanager "platform-tools" "platforms;android-36" "build-tools;36.0.0"
```

Gradle se ne instalira — projekat nosi `./gradlew`. U `.env` ide
`NATIVEPHP_ANDROID_SDK_LOCATION="$HOME/Library/Android/sdk"`.

Potpisni ključ, jednom — **ne ide u git, ne smije se izgubiti** (bez njega nema
ažuriranja u Play Store-u):

```bash
keytool -genkeypair -v -keystore ~/.ppkalkulatron/release.keystore \
    -alias ppkalkulatron -keyalg RSA -keysize 2048 -validity 10000 \
    -dname "CN=PlusPlus IT, O=PlusPlus IT d.o.o., L=Banja Luka, C=BA"
```

APK:

```bash
npm run build     # bez ovoga se pakuju stari CSS i JS

php artisan native:package android --build-type=release \
    --keystore="$HOME/.ppkalkulatron/release.keystore" \
    --keystore-password=... --key-alias=ppkalkulatron --key-password=...
```

Izlazi u `nativephp/android/app/build/outputs/apk/release/app-release.apk`.
Prvi build ~20 min (Gradle, NDK, CMake — oko 2 GB), sljedeći par minuta.

Ako javi **„TTY mode requires /dev/tty"**, komanda je pokrenuta bez terminala.
Projekat je već spreman, pa Gradle ide ručno:

```bash
cd nativephp/android && ./gradlew assembleRelease --no-daemon \
    -PMYAPP_UPLOAD_STORE_FILE="$HOME/.ppkalkulatron/release.keystore" \
    -PMYAPP_UPLOAD_KEY_ALIAS=ppkalkulatron \
    -PMYAPP_UPLOAD_STORE_PASSWORD=... -PMYAPP_UPLOAD_KEY_PASSWORD=...
```

Dokumentacija: [Android build](https://nativephp.com/docs/mobile/1/getting-started/building-your-app) ·
[potpisivanje](https://nativephp.com/docs/mobile/1/distribution/android)

## iPhone

```bash
sudo xcode-select -s /Applications/Xcode.app/Contents/Developer
sudo xcodebuild -license accept
brew install cocoapods

npm run build
php artisan native:run ios                      # simulator
php artisan native:package ios --build-type=release
```

Za pravi uređaj treba [Apple Developer](https://developer.apple.com/programs/)
nalog (99 USD/god) i `NATIVEPHP_DEVELOPMENT_TEAM` u `.env`.
`php artisan native:credentials` vodi kroz potpisivanje.

**Besplatno slanje iOS builda drugome ne postoji.** TestFlight traži plaćen nalog;
Firebase App Distribution za iOS traži potpisan IPA, što opet traži isti nalog.
Sa besplatnim Apple ID-em aplikacija se može staviti samo na *svoj* telefon kroz
Xcode i traje 7 dana.

Dokumentacija: [iOS distribucija](https://nativephp.com/docs/mobile/1/distribution/ios)

## Slanje na test

**Android** — pošalji APK kao datoteku (mail, WeTransfer, Drive). Tester dopusti
instalaciju iz nepoznatih izvora; Play Protect javi „nepoznat programer" →
*Ipak instaliraj*. Za više testera:
[Firebase App Distribution](https://firebase.google.com/docs/app-distribution)
(besplatno, prima gotov APK — **ne** traži Firebase u aplikaciji).

**Preglednik** — `php artisan serve --host=0.0.0.0 --port=8080`, pa
`http://<tvoja-ip>:8080`; izvan mreže `cloudflared tunnel --url http://localhost:8080`.
Baza je zajednička, i fiskalni uređaj se poziva sa servera, ne sa testerovog telefona.

## Identitet aplikacije

```bash
php artisan app:brand-assets    # public/icon.png 1024², splash{,-dark}.png 1080×1920
```

| Šta | Gdje | Vrijednost |
|---|---|---|
| Ime | `.env` `APP_NAME` | ppKalkulatron |
| Bundle ID | `.env` `NATIVEPHP_APP_ID` | com.plusplusit.ppkalkulatron |
| Verzija | `.env` `NATIVEPHP_APP_VERSION` / `_CODE` | 0.1.0 / 1 |
| Boja teme | `nativephp.android.theme.color_primary` | `#F59E0B` |
| Orijentacija | `nativephp.orientation` | portret |
| iPad | `nativephp.ipad` | isključen (u App Store-u se ne može povući) |

[Konfiguracija](https://nativephp.com/docs/mobile/1/getting-started/configuration) ·
[ikona](https://nativephp.com/docs/mobile/1/the-basics/app-icon) ·
[splash](https://nativephp.com/docs/mobile/1/the-basics/splash-screens)

## Zamke koje su već pregažene

- **Sigurne zone.** Android 15 crta od ivice do ivice, pa je zaglavlje stajalo pod
  status trakom. Klase `.safe-top`, `.safe-bottom`, `.pb-nav` u `app.css`.
- **`back()` u webviewu** je znao odvesti na POST-only rutu → *„GET nije podržan"*.
  Svi kontroleri podešavanja preusmjeravaju eksplicitno na imenovanu rutu.
- **`storage/framework` ne ide u paket** — `bootstrap/app.php` ga napravi prije
  nego što se aplikacija podigne, inače pakovanje padne na *„valid cache path"*.
- **PHP na uređaju je bez ICU-a** — iznos slovima računa `App\Support\SpelledAmount`.
- **`APP_DEBUG` i `APP_ENV` se brišu iz paketa** (`cleanup_env_keys`), pa aplikacija
  ide kao `production` sa isključenim debugom.
- **`MAIL_MAILER=smtp`**, ne `log` — inače bi na telefonu poruke tiho išle u log.
- **`npm run build` u Sail kontejneru ne radi** (rolldown nema linux-arm64 binding).
- **`google-services.json` stoji u `firebase/`**, ne u korijenu — vidi
  [firebase/README.md](firebase/README.md).
- **Verzija** se diže u `.env` ili `php artisan native:release`; Play Store odbija
  isti `version_code` dva puta.
