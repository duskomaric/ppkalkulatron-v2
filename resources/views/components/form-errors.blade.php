@if ($errors->any())
    <div class="space-y-1 rounded-2xl border border-[var(--color-error)]/30 bg-[var(--color-error)]/10 p-3">
        @foreach ($errors->all() as $message)
            <p class="text-[11px] font-bold text-[var(--color-error)]">{{ $message }}</p>
        @endforeach
    </div>
@endif
