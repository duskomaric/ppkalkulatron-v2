# ppKalkulatron

Mobilna aplikacija za izradu računa, fiskalizaciju preko OFS ESIR uređaja i slanje
dokumenata emailom. Podaci se čuvaju lokalno na uređaju. PHP komunicira s fiskalnim
uređajem, pa lokalna kasa na istoj mreži radi bez javne IP adrese.

## Lokalni razvoj

```bash
composer install
npm install
php artisan migrate
npm run build
```

Provjere prije izmjena:

```bash
php artisan test --compact
vendor/bin/pint --dirty --format agent
```

## Fiskalizacija i backup

- Fiskalni dokumenti se čuvaju privatno kao PNG, PDF ili HTML, uz fiskalni zapis.
- PDF računi i fiskalni dokumenti se mogu poslati emailom.
- Backup šalje ZIP sa svim PDF računima, fiskalnim dokumentima i manifestom.
- Podešavanja fiskalnog uređaja, SMTP-a i backupa nalaze se u aplikaciji.

## Android release

Upute za potpisani APK, automatsko povećanje verzije i Firebase distribuciju su u
[BUILD.md](BUILD.md).
