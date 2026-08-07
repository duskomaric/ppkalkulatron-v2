<x-drawer title="Podešavanja" state="settingsDrawer">
    <div class="flex flex-col gap-3">
        <p class="text-[10px] font-black uppercase tracking-widest text-[var(--color-text-dim)] px-1 pb-1">
            Zajednička podešavanja
        </p>

        {{-- Vodič je drawer, pa se ovdje samo prebacuje panel. --}}
        <x-drawer-nav-item icon="check" title="Početno podešavanje"
                           description="Koraci do prvog računa i šta još fali"
                           x-on:click="settingsDrawer = false; setupDrawer = true" />
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
            Podaci i backup
        </p>

        <x-drawer-nav-item :href="route('settings.backup.edit')" icon="mail" title="Arhiva na email"
                           description="PDF računi i fiskalni dokumenti za knjigovođu" />
        <x-drawer-nav-item :href="route('settings.database.edit')" icon="archive" title="Backup aplikacije"
                           description="Puna kopija, vraćanje i reset aplikacije" />

        <p class="text-[10px] font-black uppercase tracking-widest text-[var(--color-text-dim)] px-1 pt-4 pb-1">
            Aplikacija
        </p>

        <x-drawer-nav-item :href="route('settings.menu.edit')" icon="monitor" title="Izgled i navigacija"
                           description="Tema i raspored modula" />
        <x-drawer-nav-item :href="route('settings.pin.edit')" icon="lock" title="PIN"
                           description="Zaključavanje aplikacije pri pokretanju" />
        <x-drawer-nav-item :href="route('settings.diagnostics.edit')" icon="activity" title="Dijagnostika"
                           description="Sigurno slanje tehničkog loga" />
        <x-drawer-nav-item :href="route('help')" icon="info" title="Pomoć"
                           description="Kako aplikacija radi" />
    </div>
</x-drawer>
