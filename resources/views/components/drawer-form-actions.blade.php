@props(['label', 'delete' => null, 'cancel' => null])

{{--
    Dugme za brisanje cilja formu izvan ove preko atributa form="delete-entity".
    Sama forma se renderuje sa <x-delete-form>, poslije </form> — ugniježdena forma
    nije ispravan HTML i preglednik je izmjesti, pa čuvanje ode na pogrešnu rutu.
--}}
<div class="flex flex-col gap-2 pt-2">
    <button type="submit" :disabled="$data.saving"
            class="w-full py-3.5 bg-primary text-white rounded-xl font-black text-[11px] uppercase tracking-[0.2em] shadow-glow-primary hover:scale-[1.02] active:scale-95 transition-all flex items-center justify-center gap-2 cursor-pointer disabled:opacity-60">
        <span x-show="$data.saving" x-cloak class="h-4 w-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
        <span>{{ $label }}</span>
    </button>

    @if ($delete)
        <button type="submit" form="delete-entity"
                class="w-full py-3.5 bg-red-500/10 text-red-500 border border-red-500/20 rounded-xl font-black text-[11px] uppercase tracking-[0.15em] hover:bg-red-500 hover:text-white transition-all cursor-pointer">
            Obriši
        </button>
    @endif

    {{-- Na punoj stranici nema drawera koji bi se zatvorio, pa se ide nazad. --}}
    <x-drawer-secondary-button label="Odustani"
                               :x-on:click="'$data.closeForm ? $data.closeForm() : (window.location = '.\App\Support\Js::from($cancel ?? url()->previous()).')'" />
</div>
