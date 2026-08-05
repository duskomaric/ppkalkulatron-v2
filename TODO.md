- [x] neke rute potrebno prevesti na srpski - tipa unlock i sl
      → `/unlock` → `/otkljucaj`, `/lock` → `/zakljucaj`, `/dijagnostika/mobile` → `/dijagnostika/mobilna`,
      `/racuni/{invoice}/mail` → `/racuni/{invoice}/posalji` (imena ruta nisu mijenjana).
      `/podesavanja/backup` → `/podesavanja/arhiva` (uz novu labelu „Arhiva na email“), puna kopija je na
      `/podesavanja/backup-aplikacije`. `/podesavanja/mail` ostaje dok se labela u meniju ne prevede.
- [x] u poreskim stopama se pominju oznake sa cirilicom - ukloniti - nema vrijednost za korisnika
      → tekst na fiskalizaciji preformulisan, prikaz stopa ostaje isti.
- [x] kada se racun pregleda na desktopu, box se nalazi lijevo a treba da uzima max sirinu layouta - to je problem sa svim stranicama (novi racun djeluje ok)
      → uklonjen `max-w-3xl` sa svih stranica (računi, klijenti, artikli, valute, bankovni računi, profil, pomoć, podešavanja).
- [x] ako se u tekstu pominje ppKalkulatron to moramo prepraviti da koristi ime app iz env
      → svi prikazi koriste `config('app.name')` (naslovi, otključavanje, pomoć, mailovi, dijagnostika, verzija).
- [x] provjeriti imaju li statusi smisla nakon sto se stornira i fiskalizuje storno - predlozi izmjene - nema starih podataka - radimo migrate:fresh
      → statusi: Nacrt → Fiskalizovan (prodaja), Storniranje → Storniran (storno dokument).
      → original nakon storna ostaje Fiskalizovan, a poništenje se prikazuje kao izvedena oznaka.
- [x] na racunima, artiklima, klijentima treba da imamo samo jedan main link do help sekcije - tako treba da je na kreiranju i pregledu
      → uklonjeni help linkovi po sekcijama, ostaje jedan u zaglavlju stranice.
- [ ] u podesavanjima imamo sifrarnici menu labelu ali je prazno - prebaciti iz glavnog menija bankovne racune i valute - u vise koji nakon toga ostaje prazan mozemo da prebacimo neki drugi page klijenti, racuni, artikli a poslije cemo imati i druge - potrebno i podesavanja navigacije prepraviti
- [x] predlozak racuna treba da uvecamo i izbacimo sam pregled jer vec na mailim pregledima treba da je sve vidljivo, prikazi vise je lose rijesenje (smisliti nesto drugo), na desktop se lose prikazuje, sekciju generalno reorganizovati logicnije, prikaz predlozaka dodati predefinisane filtere
      → minijatura prikazuje cijelu A4 stranu (bez odsijecanja), veća je i bez praznog prostora; tri u redu na desktopu.
      → uklonjena stranica „Puni pregled“ i „Prikaži još predložaka“; svi predlošci su u jednoj mreži.
      → dodati filteri: stil (Poslovni, Kod i editor, Terminal, Signal i mreža) i tema (Svijetli, Tamni) + brojač i oznaka odabranog.
      → „Generalno“ podijeljeno na tri kartice: Numeracija računa, Zadane vrijednosti novog računa, Izgled računa.

Napomene (nisu zadaci):
- app je u test fazi i nema potrebe da pazimo na migracije jer radimo migrate:fresh, nemamo backward compatability
- [necemo sad raditi] buduci moduli za dodavanje su ponude, predracuni, knjiga prihoda, dodavanje na sam racun stanje duga po klijentu tj kao mini finansijsku karticu gdje se vide uplate za prethodni i trenutni mjesec a ako nema onda poslednja uplata - to bi znacilo izmjenu svih predlozaka
