<x-drawer title="Više modula" state="moreDrawer">
    <div class="flex flex-col gap-3">
        @foreach ($drawerItems as $item)
            <x-drawer-nav-item :href="$item['href']" :icon="$item['icon']" :title="$item['title']"
                               description="Otvorite modul iz dodatne navigacije" />
        @endforeach
    </div>
</x-drawer>
