@props(['value' => '', 'placeholder' => 'Pretraga…'])

<form method="GET" class="mb-4">
    <div class="relative">
        <x-icon name="search" class="h-4 w-4 absolute left-4 top-1/2 -translate-y-1/2 text-[var(--color-text-dim)]" />
        <input type="search" name="q" value="{{ $value }}" placeholder="{{ $placeholder }}"
               class="w-full pl-11 pr-4 py-3 bg-[var(--color-glass)] backdrop-blur-xl border border-[var(--color-border)] rounded-xl font-bold text-sm outline-none focus:border-primary/40 transition-all">
    </div>
</form>
