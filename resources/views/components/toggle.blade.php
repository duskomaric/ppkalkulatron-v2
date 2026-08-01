@props(['name' => null, 'model' => null, 'checked' => false, 'label' => null, 'labelExpr' => null, 'value' => '1', 'hidden' => true, 'id' => null])

@php($inputId = $id ?? ($name ? \Illuminate\Support\Str::slug($name.'-'.$value) : null))

<label @if ($inputId) for="{{ $inputId }}" @endif class="flex items-center gap-3 border bg-[var(--color-surface)] border-[var(--color-border)] p-3 rounded-xl group cursor-pointer transition-all">
    {{-- Bez skrivenog polja isključen prekidač ne bi ništa poslao; u listama to ne treba. --}}
    @if ($name && $hidden)<input type="hidden" name="{{ $name }}" value="0">@endif

    <div class="relative flex items-center">
        <input type="checkbox" @if ($inputId) id="{{ $inputId }}" @endif @if ($name) name="{{ $name }}" value="{{ $value }}" @endif
               @if ($model) x-model="{{ $model }}" @else @checked($checked) @endif
               {{ $attributes->class('sr-only peer') }}>
        <div class="relative h-5 w-9 rounded-full bg-[var(--color-border-strong)] transition-colors peer-focus:ring-4 peer-focus:ring-primary/10 peer-checked:bg-primary after:absolute after:top-[2px] after:left-[2px] after:h-4 after:w-4 after:rounded-full after:border after:border-[var(--color-border-strong)] after:bg-[var(--color-text-dim)] after:transition-all peer-checked:after:translate-x-full peer-checked:after:bg-[var(--color-surface)]"></div>
    </div>

    @if ($labelExpr)
        <span class="text-[13px] font-bold text-[var(--color-text-muted)]" x-text="{{ $labelExpr }}"></span>
    @elseif ($label)
        <span class="text-[13px] font-bold text-[var(--color-text-muted)]">{{ $label }}</span>
    @else
        {{ $slot }}
    @endif
</label>
