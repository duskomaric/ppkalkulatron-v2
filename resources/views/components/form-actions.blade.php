@props(['label', 'delete' => null, 'cancel' => null])

{{-- Brisanje je odvojena forma kako se forme za čuvanje nikada ne bi ugnijezdile. --}}
<div class="flex flex-col gap-2 pt-2">
    <x-button class="w-full !py-3.5 !text-[11px] !font-black !uppercase !tracking-[0.2em] hover:scale-[1.02] active:scale-95 disabled:opacity-60">
        <span>{{ $label }}</span>
    </x-button>

    @if ($delete)
        <x-button variant="danger" type="submit" form="delete-entity"
                  class="w-full !py-3.5 !text-[11px] !font-black !uppercase !tracking-[0.15em]">
            Obriši
        </x-button>
    @endif

    <x-button variant="ghost" :href="$cancel ?? url()->previous()"
              class="w-full !py-3.5 !text-[10px] !font-black !uppercase !tracking-widest">
        Odustani
    </x-button>
</div>
