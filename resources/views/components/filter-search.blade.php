@props(['value' => '', 'placeholder' => 'Pretraži...'])

{{--
    Traženje se pokreće samo od sebe. Forma filtera nema submit dugme, a uz više
    polja koja blokiraju implicitno slanje ni Enter je ne bi poslao — bez ovoga se
    upisani termin nije mogao primijeniti bez diranja nekog drugog filtera.
--}}
<div class="relative">
    <div class="absolute left-3 top-1/2 -translate-y-1/2 text-[var(--color-text-dim)]">
        <x-icon name="search" class="h-4 w-4" />
    </div>

    <input name="q" value="{{ $value }}" placeholder="{{ $placeholder }}" aria-label="Pretraga"
           x-on:input.debounce.600ms="$el.form.requestSubmit()"
           x-on:keydown.enter.prevent="$el.form.requestSubmit()"
           class="w-full h-11 pl-10 pr-3 rounded-full border border-[var(--color-border)] bg-[var(--color-surface)] text-sm font-bold text-[var(--color-text-main)] placeholder:text-[var(--color-text-dim)] focus:outline-none focus:border-primary/60 focus:ring-4 focus:ring-primary/10">
</div>
