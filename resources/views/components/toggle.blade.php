@props(['name', 'checked' => false, 'label' => null, 'value' => '1', 'hidden' => true, 'id' => null])

@php($inputId = $id ?? \Illuminate\Support\Str::slug($name.'-'.$value))

<label for="{{ $inputId }}" class="flex items-center gap-3 border bg-[var(--color-surface)] border-[var(--color-border)] p-3 rounded-xl group cursor-pointer transition-all">
    {{-- Bez skrivenog polja isključen prekidač ne bi ništa poslao; u listama to ne treba. --}}
    @if ($hidden)<input type="hidden" name="{{ $name }}" value="0">@endif

    <div class="relative flex items-center">
        <input type="checkbox" id="{{ $inputId }}" name="{{ $name }}" value="{{ $value }}"
               @checked($checked) {{ $attributes }} class="sr-only peer">
        <div class="w-9 h-5 bg-[var(--color-border-strong)] rounded-full peer-focus:outline-none peer-checked:bg-primary after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:h-4 after:w-4 after:rounded-full after:bg-gray-400 after:border after:border-gray-300 after:transition-all peer-checked:after:translate-x-full peer-checked:after:bg-white relative"></div>
    </div>

    @if ($label)
        <span class="text-[13px] font-bold text-[var(--color-text-muted)]">{{ $label }}</span>
    @else
        {{ $slot }}
    @endif
</label>
