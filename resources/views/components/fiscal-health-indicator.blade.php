@props(['health', 'url', 'variant' => 'icon'])

{{--
    Stanje fiskalne kase: u zaglavlju kao dugme u boji stanja sa tačkom koja
    pulsira dok provjera traje, a u sekcijama kao sitna ikona uz naslov.
--}}
<span x-data="backgroundChecks({ url: @js($url), initial: @js($health) })" class="inline-flex shrink-0 items-center self-center">
    @if ($variant === 'pill')
        {{-- Bez teksta: ikona u boji stanja, sa tačkom koja pulsira dok provjera traje. --}}
        <a href="{{ route('settings.fiscal.edit') }}" :title="health.label" :aria-label="health.label"
           class="relative inline-flex h-11 w-11 items-center justify-center rounded-xl border transition-colors"
           :class="{
               'border-emerald-500/30 bg-emerald-500/10 text-emerald-500 hover:bg-emerald-500/20': health.state === 'ready',
               'border-amber-500/30 bg-amber-500/10 text-amber-500 hover:bg-amber-500/20': health.state === 'pin_required' || health.state === 'unknown',
               'border-red-500/30 bg-red-500/10 text-red-500 hover:bg-red-500/20': health.state === 'unavailable' || health.state === 'no_element',
           }">
            <x-icon name="printer" class="h-4 w-4" />

            <span class="absolute -right-0.5 -top-0.5 flex h-2.5 w-2.5">
                <span x-show="checking" x-cloak class="absolute inline-flex h-full w-full animate-ping rounded-full opacity-70"
                      :class="{
                          'bg-emerald-500': health.state === 'ready',
                          'bg-amber-500': health.state === 'pin_required' || health.state === 'unknown',
                          'bg-red-500': health.state === 'unavailable' || health.state === 'no_element',
                      }"></span>
                <span class="relative inline-flex h-2.5 w-2.5 rounded-full ring-2 ring-[var(--color-bg)]"
                      :class="{
                          'bg-emerald-500': health.state === 'ready',
                          'bg-amber-500': health.state === 'pin_required' || health.state === 'unknown',
                          'bg-red-500': health.state === 'unavailable' || health.state === 'no_element',
                      }"></span>
            </span>

            {{-- Stanje koje traži potez korisnika ne smije ostati samo boja. --}}
            <span class="sr-only" x-text="health.label"></span>
        </a>
    @else
        <a href="{{ route('settings.fiscal.edit') }}" :title="health.label" :aria-label="health.label"
           class="relative inline-flex h-7 w-7 items-center justify-center transition-colors"
           :class="{
               'text-emerald-500': health.state === 'ready',
               'text-amber-500': health.state === 'pin_required' || health.state === 'unknown',
               'text-red-500': health.state === 'unavailable' || health.state === 'no_element',
           }">
            <x-icon name="printer" class="h-4 w-4" />
            <span class="absolute right-0.5 top-0.5 h-1.5 w-1.5 rounded-full ring-2 ring-[var(--color-bg)]" :class="{
                'bg-emerald-500': health.state === 'ready',
                'bg-amber-500': health.state === 'pin_required' || health.state === 'unknown',
                'bg-red-500': health.state === 'unavailable' || health.state === 'no_element',
            }"></span>
        </a>
    @endif
</span>
