{{-- Greške iz XHR odgovora; pune stranice ih i dalje prikazuju uz polje. --}}
<template x-if="Object.keys($data.formErrors || {}).length">
    <div data-error-summary class="p-3 rounded-2xl border border-red-500/30 bg-red-500/10 space-y-1">
        <template x-for="messages in Object.values($data.formErrors)" :key="messages[0]">
            <p class="text-[11px] font-bold text-red-500" x-text="messages[0]"></p>
        </template>
    </div>
</template>
