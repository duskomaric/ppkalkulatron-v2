@props(['name', 'value' => ''])

<input type="date" name="{{ $name }}" value="{{ $value }}" onchange="this.form.requestSubmit()"
       class="h-9 w-full min-w-[120px] px-3 rounded-full border border-[var(--color-border)] bg-[var(--color-surface)] text-xs font-black uppercase tracking-[0.12em] text-[var(--color-text-main)] focus:outline-none focus:border-primary/60 focus:ring-4 focus:ring-primary/10">
