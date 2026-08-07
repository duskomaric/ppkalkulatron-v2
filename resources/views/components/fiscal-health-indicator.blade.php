@props(['health', 'url', 'variant' => 'icon'])

{{--
    Stanje fiskalne kase. U zaglavlju stoji kao pilula sa tačkom i kratkim
    opisom, a u sekcijama kao sama ikona — ista podloga, dva izgleda.
--}}
@php
    $tone = [
        'ready' => 'text-emerald-500',
        'pin_required' => 'text-amber-500',
        'unknown' => 'text-amber-500',
        'no_element' => 'text-red-500',
        'unavailable' => 'text-red-500',
    ];
    $dot = [
        'ready' => 'bg-emerald-500',
        'pin_required' => 'bg-amber-500',
        'unknown' => 'bg-amber-500',
        'no_element' => 'bg-red-500',
        'unavailable' => 'bg-red-500',
    ];
    $short = [
        'ready' => 'Kasa',
        'pin_required' => 'PIN',
        'unknown' => 'Kasa',
        'no_element' => 'Element',
        'unavailable' => 'Kasa',
    ];
@endphp

<span x-data="backgroundChecks({ url: @js($url), initial: @js($health) })" class="inline-flex shrink-0 items-center self-center">
    @if ($variant === 'pill')
        <a href="{{ route('settings.fiscal.edit') }}" :title="health.label" :aria-label="health.label"
           class="inline-flex h-11 items-center gap-2 rounded-xl border px-2.5 transition-colors sm:px-3"
           :class="{
               'border-emerald-500/30 bg-emerald-500/10 text-emerald-500 hover:bg-emerald-500/20': health.state === 'ready',
               'border-amber-500/30 bg-amber-500/10 text-amber-500 hover:bg-amber-500/20': health.state === 'pin_required' || health.state === 'unknown',
               'border-red-500/30 bg-red-500/10 text-red-500 hover:bg-red-500/20': health.state === 'unavailable' || health.state === 'no_element',
           }">
            <span class="relative flex h-2 w-2 shrink-0">
                {{-- Dok se provjera vrti, tačka pulsira; inače mirno stoji. --}}
                <span x-show="checking" class="absolute inline-flex h-full w-full animate-ping rounded-full opacity-60"
                      :class="{
                          'bg-emerald-500': health.state === 'ready',
                          'bg-amber-500': health.state === 'pin_required' || health.state === 'unknown',
                          'bg-red-500': health.state === 'unavailable' || health.state === 'no_element',
                      }"></span>
                <span class="relative inline-flex h-2 w-2 rounded-full"
                      :class="{
                          'bg-emerald-500': health.state === 'ready',
                          'bg-amber-500': health.state === 'pin_required' || health.state === 'unknown',
                          'bg-red-500': health.state === 'unavailable' || health.state === 'no_element',
                      }"></span>
            </span>
            <x-icon name="printer" class="h-4 w-4" />
            <span class="hidden text-[10px] font-black uppercase tracking-widest lg:inline"
                  x-text="{
                      ready: 'Kasa',
                      pin_required: 'Traži PIN',
                      unknown: 'Kasa',
                      no_element: 'Bez elementa',
                      unavailable: 'Nema kase',
                  }[health.state]"></span>
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
