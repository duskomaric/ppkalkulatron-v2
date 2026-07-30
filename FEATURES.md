# ppKalkulatron v2 — popis feature-a

Izvučeno iz v1 koda (`ppKalkulatron-api` + `ppKalkulatron-pwa`), ne po sjećanju.
v1 služi kao referenca; radimo jedan po jedan, uz unaprijeđenja iz kolone "napomena".

---

## 1. Osnove

| Feature | v1 | Napomena za v2 |
|---|---|---|
| Prijava (token) | ✅ | Na mobitelu token u Secure Storage, ne u localStorage |
| Više kompanija po korisniku, prebacivanje | ✅ | v1 je imao bug: state po komponenti. U v2 jedan izvor istine od početka |
| Uloge korisnika | ✅ | |
| Moduli po kompaniji (uključi/isključi) | ✅ | 12 definisanih, 6 implementirano |
| Podešavanje menija (koji modul gdje) | ✅ | |
| Jezik korisnika | ✅ | 5 jezika: en, bs, hr, sr_Latn, sr_Cyrl |
| Tema / vizualne postavke | ✅ | |

## 2. Dokumenti

| Feature | v1 | Napomena za v2 |
|---|---|---|
| Računi (fakture) | ✅ | |
| Predračuni | ✅ | |
| Ponude | ✅ | |
| Ugovori | ✅ | nema modul u meniju, samo API |
| Konverzije: ponuda → predračun → račun | ✅ | |
| Ugovor → račun | ✅ | |
| Storno / refundacija | ✅ | |
| Ponavljajući računi (recurring) | ✅ | artisan komanda |
| Numeracija dokumenata | ✅ | **v2: odlučiti offline strategiju** (vidi Rizici) |
| Početni broj po tipu dokumenta | ✅ | |
| Prefiks, broj nula, godišnji reset | ✅ | |

## 3. Fiskalizacija (OFS)

| Feature | v1 | Napomena za v2 |
|---|---|---|
| Cloud uređaj (pos.ofs.ba) | ✅ | |
| Lokalni ESIR na LAN-u | ⚠️ | **glavni razlog za v2** — u NativePHP-u poziv ide iz PHP-a, `OFSService` se koristi kakav je |
| Fiskalizacija računa | ✅ | |
| Kopija fiskalnog računa | ✅ | |
| Refundacija | ✅ | |
| Slika fiskalnog računa (PNG/PDF/HTML) | ✅ | izmjereno na pravoj kasi, sva tri formata rade |
| Verifikacijski URL / QR | ✅ | |
| PIN sigurnosnog elementa | ✅ | |
| Test veze (attention / settings / status) | ✅ | |
| Skeniranje mreže za uređaj | ✅ | u native buildu radi bez ograničenja |
| Veleprodaja (VP: prefiks) | ✅ | |
| Povrat izgubljenih slika sa OFS-a | ✅ | artisan komanda |
| **GTIN artikla** | ❌ | v1 šalje izmišljen broj — v2: pravo polje na artiklu |
| **Poreske oznake iz `currentTaxRates`** | ❌ | v1 hardkodira fallback `A` = 9% PDV |

## 4. Šifarnici

| Feature | v1 | Napomena za v2 |
|---|---|---|
| Klijenti | ✅ | JIB / PDV jasno označeni |
| Artikli | ✅ | + GTIN, + zadnja cijena |
| Valute + kursevi | ✅ | sync sa fixer.io |
| Bankovni računi | ✅ | prikaz na dokumentima |
| Poreske stope | ✅ | |

## 5. Izlazi

| Feature | v1 | Napomena za v2 |
|---|---|---|
| PDF računa/predračuna/ponude | ✅ | 4 šablona × 3 tipa = 12 |
| **PDF na više jezika** | ❌ | v1 su svi hardkodirani na bosanski |
| Slanje na email | ✅ | SMTP po kompaniji |
| Prilog: PDF + slika fiskalnog | ✅ | |
| Knjiga prihoda | ✅ | + PDF izvoz |
| Protokol | ✅ | |

## 6. Ostalo

| Feature | v1 | Napomena za v2 |
|---|---|---|
| Pomoć / dokumentacija u aplikaciji | ✅ | |
| Filament admin panel | ✅ | |
| 29 postavki po kompaniji | ✅ | |

---

## Novo u v2 (jer sad znamo šta želimo)

- **Offline rad** — izdavanje računa kad internet padne a kasa je na mreži
- **Native poziv na kasu** — bez service workera, mixed contenta i PNA
- Token u Secure Storage
- PDF-ovi na jeziku dokumenta
- GTIN i poreske oznake ispravno
- Slike fiskalnih računa: odlučiti disk vs baza na osnovu gdje se hostuje

## Rizici koje treba riješiti prije koda

1. **Numeracija offline.** v1 izvodi sljedeći broj iz dokumenata u centralnoj bazi.
   Dva uređaja offline izvela bi isti broj — a to je fiskalni dokument.
   Opcije: opseg po uređaju, prefiks po uređaju, ili numeracija samo online.
2. **Sinhronizacija.** Šta je izvor istine — uređaj ili server? Šta kad se razlikuju?
3. **iOS lokalna mreža.** ATS + dozvola korisnika za lokalnu mrežu — dokazati u spike-u.
