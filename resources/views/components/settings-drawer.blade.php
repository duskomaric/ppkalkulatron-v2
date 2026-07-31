{{--
    Struktura prati v1 SettingsDrawer: naslovi grupa pa DrawerNavItem sa opisom.
    Bez licence i vizuelnih podešavanja — nema pretplate, a svi moduli su vidljivi.
--}}
<x-drawer title="Podešavanja" state="settingsDrawer">
    <div class="flex flex-col gap-3">
        <p class="text-[10px] font-black uppercase tracking-widest text-[var(--color-text-dim)] px-1 pb-1">
            Zajednička podešavanja
        </p>

        <x-drawer-nav-item :href="route('settings.company.edit')" icon="building" title="Profil kompanije"
                           description="Podaci o firmi, adresa i JIB/PIB" />
        <x-drawer-nav-item :href="route('settings.mail.edit')" icon="mail" title="Mail"
                           description="Slanje računa, SMTP" />

        <p class="text-[10px] font-black uppercase tracking-widest text-[var(--color-text-dim)] px-1 pt-4 pb-1">
            Podešavanja dokumenata
        </p>

        <x-drawer-nav-item :href="route('settings.general.edit')" icon="cog" title="Generalno"
                           description="Numeracija, predložak i napomene" />
        <x-drawer-nav-item :href="route('settings.fiscal.edit')" icon="file-text" title="Fiskalizacija"
                           description="OFS ESIR – cloud ili lokalni uređaj" />



        <p class="text-[10px] font-black uppercase tracking-widest text-[var(--color-text-dim)] px-1 pt-4 pb-1">
            Šifarnici
        </p>

        <x-drawer-nav-item :href="route('clients.index')" icon="contact" title="Klijenti"
                           description="Kupci, JIB i PDV broj" />
        <x-drawer-nav-item :href="route('articles.index')" icon="boxes" title="Artikli"
                           description="Usluge i proizvodi, jedinice i poreske oznake" />
        <x-drawer-nav-item :href="route('bank-accounts.index')" icon="credit-card" title="Bankovni računi"
                           description="Računi koji se ispisuju na dokumentima" />
        <x-drawer-nav-item :href="route('currencies.index')" icon="hash" title="Valute"
                           description="Valute i kursevi prema konvertibilnoj marki" />

        <p class="text-[10px] font-black uppercase tracking-widest text-[var(--color-text-dim)] px-1 pt-4 pb-1">
            Aplikacija
        </p>

        <x-drawer-nav-item :href="route('settings.pin.edit')" icon="lock" title="PIN"
                           description="Zaključavanje aplikacije pri pokretanju" />
        <x-drawer-nav-item :href="route('help')" icon="info" title="Pomoć"
                           description="Kako aplikacija radi" />
    </div>
</x-drawer>
