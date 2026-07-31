# google-services.json

Ovdje stoji, a ne u korijenu projekta, namjerno.

NativePHP pri svakom Android buildu kopira `google-services.json` iz korijena ili
iz `nativephp/resources/` u Android projekat, a generisani `app/build.gradle.kts`
tada uslovno primijeni `com.google.gms.google-services`. Taj Gradle plugin **nije
na classpathu** dok se ne instalira NativePHP-ov Firebase plugin, pa build padne:

```
Plugin with id 'com.google.gms.google-services' not found.
```

Provjereno `./gradlew tasks` sa fajlom u korijenu.

## Kad zatrebaju push notifikacije

```bash
composer require nativephp/mobile-firebase
cp firebase/google-services.json .
```

Plugin deklariše Gradle plugin i doda Firebase SDK. Do tada fajl stoji ovdje i ne
smeta buildu.

Napomena: `google-services.json` nije tajna — ide u svaki Android APK i može se
pročitati iz njega. Ipak ne ide u git jer je vezan za konkretan Firebase projekat.
