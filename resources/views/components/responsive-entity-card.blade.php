@props(['href' => null])

{{-- v1 ResponsiveEntityCard: zaseban raspored za telefon i za desktop. --}}
<x-entity-card :href="$href" {{ $attributes }}>
    <div class="md:hidden">{{ $mobile }}</div>
    <div class="hidden md:block">{{ $desktop }}</div>
</x-entity-card>
