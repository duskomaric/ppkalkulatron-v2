@props(['href' => null])

<x-entity-card :href="$href" {{ $attributes }}>
    <div class="md:hidden">{{ $mobile }}</div>
    <div class="hidden md:block">{{ $desktop }}</div>
</x-entity-card>
