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



        <p class="text-[10px] font-black uppercase tracking-widest text-[var(--color-text-dim)] px-1 pt-4 pb-1">
            Šifarnici
        </p>

        <x-drawer-nav-item :href="route('clients.index')" icon="contact" title="Klijenti"
                           description="Kupci, JIB i PDV broj" />
        <x-drawer-nav-item :href="route('articles.index')" icon="boxes" title="Artikli"
                           description="Usluge i proizvodi, jedinice i poreske oznake" />

        <p class="text-[10px] font-black uppercase tracking-widest text-[var(--color-text-dim)] px-1 pt-4 pb-1">
            Aplikacija
        </p>

        <x-drawer-nav-item :href="route('settings.pin.edit')" icon="lock" title="PIN"
                           description="Zaključavanje aplikacije pri pokretanju" />
        <x-drawer-nav-item :href="route('help')" icon="info" title="Pomoć"
                           description="Kako aplikacija radi" />
    </div>
</x-drawer>
