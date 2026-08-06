@props(['health', 'url'])

<span x-data="backgroundChecks({ url: @js($url), initial: @js($health) })" class="inline-flex shrink-0 items-center self-center">
    <a href="{{ route('settings.fiscal.edit') }}" :title="health.label" :aria-label="health.label"
       class="relative inline-flex h-7 w-7 items-center justify-center transition-colors"
       :class="{
           'text-emerald-500': health.state === 'ready',
           'text-amber-500': health.state === 'pin_required' || health.state === 'unknown',
           'text-red-500': health.state === 'unavailable',
       }">
        <x-icon name="printer" class="h-4 w-4" />
        <span class="absolute right-0.5 top-0.5 h-1.5 w-1.5 rounded-full ring-2 ring-[var(--color-bg)]" :class="{
            'bg-emerald-500': health.state === 'ready',
            'bg-amber-500': health.state === 'pin_required' || health.state === 'unknown',
            'bg-red-500': health.state === 'unavailable',
        }"></span>
    </a>
</span>
