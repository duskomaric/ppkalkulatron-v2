@if (session('status'))
    <div class="mb-6 flex items-center gap-3 rounded-xl border-l-4 border-[var(--color-success)] bg-[var(--color-success)]/10 p-4">
        <x-icon name="check" class="h-5 w-5 text-[var(--color-success)] shrink-0" />
        <p class="text-sm font-bold">{{ session('status') }}</p>
    </div>
@endif

@if (session('error'))
    <div class="mb-6 flex items-center gap-3 rounded-xl border-l-4 border-[var(--color-error)] bg-[var(--color-error)]/10 p-4">
        <x-icon name="alert" class="h-5 w-5 text-[var(--color-error)] shrink-0" />
        <p class="text-sm font-bold">{{ session('error') }}</p>
    </div>
@endif
