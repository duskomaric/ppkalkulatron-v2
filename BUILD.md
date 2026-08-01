# Android build

Pokreni iz korijena projekta:

```bash
./build-android.sh
```

Skripta provjerava Java 21, Android SDK i release keystore, a zatim:

1. pokrene `npm run build`;
2. poveća vidljivu patch verziju, npr. `0.4.1` u `0.4.2`;
3. očisti Laravel config cache;
4. NativePHP automatski poveća Android build code;
5. napravi potpisani release APK.

Ne mijenjaj ručno `NATIVEPHP_APP_VERSION` ni `NATIVEPHP_APP_VERSION_CODE` za
standardni Android build.

Gotov APK je ovdje:

```text
nativephp/android/app/build/outputs/apk/release/app-release.apk
```

## Privremeni testni profil

Dok se interno testira, novi build automatski dobija testnu firmu, OFS testnu
kasu i SMTP. To radi samo na praznoj aplikaciji; postojeća podešavanja se ne
prepisuju. Prije javne distribucije ukloniti:

```text
database/settings/2026_08_01_162706_seed_temporary_demo_build_settings.php
app/Services/TemporaryDemoBuildSettings.php
```

Za Firebase App Distribution ručno pošalji taj APK kroz **App Distribution → Add
release**. Firebase distribuira gotov APK; ne pravi Android build umjesto projekta.

## Ako skripta stane

- Java 21 nije pronađena: instaliraj `openjdk@21`.
- Android SDK nije pronađen: provjeri `~/Library/Android/sdk`.
- Keystore nije pronađen: provjeri `credentials/app-release-key.jks`.
- Novi kod nije na telefonu: instaliraj upravo napravljeni APK preko postojeće
  aplikacije; verzija i build code su već povećani.
