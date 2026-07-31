@props(['title', 'state', 'titleExpr' => null])

{{-- Ista struktura kao v1: bottom sheet na telefonu, centrirano na desktopu. --}}
<div x-cloak x-show="{{ $state }}" class="fixed inset-0 z-[1000] flex items-end sm:items-center justify-center p-0 sm:p-4">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-[8px] animate-fade-in" @click="{{ $state }} = false"></div>

    <div class="relative w-full max-w-lg md:max-w-3xl lg:max-w-4xl bg-[var(--color-surface)]/95 backdrop-blur-2xl rounded-t-[32px] sm:rounded-[40px] shadow-[0_-20px_50px_-12px_rgba(0,0,0,0.5)] overflow-visible sm:overflow-hidden animate-slide-in-bottom border-t sm:border border-[var(--color-border)] flex flex-col max-h-[94vh] sm:max-h-[90vh]">

        <div class="flex justify-center pt-3 pb-1 sm:hidden">
            <div class="w-10 h-1 bg-[var(--color-border)] rounded-full"></div>
        </div>

        <div class="px-6 py-4 flex items-center justify-between sticky top-0 z-10">
            <div class="flex flex-col">
                <h2 class="text-lg font-black tracking-tight italic leading-tight"
                    @if ($titleExpr) x-text="{{ $titleExpr }}" @endif>{{ $title }}</h2>
                <div class="h-0.5 w-8 bg-primary rounded-full mt-1 opacity-50"></div>
            </div>

            <button type="button" @click="{{ $state }} = false" aria-label="Zatvori"
                    class="cursor-pointer h-10 w-10 flex items-center justify-center bg-[var(--color-border)] hover:bg-[var(--color-surface-hover)] rounded-[14px] text-[var(--color-text-muted)] hover:text-[var(--color-text-main)] transition-all border border-[var(--color-border)] group active:scale-90">
                <x-icon name="x" class="h-5 w-5 transition-transform group-hover:rotate-90" />
            </button>
        </div>

        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-1/2 h-1 bg-primary/20 blur-md pointer-events-none"></div>

        <div class="px-6 pb-24 sm:pb-12 overflow-y-auto flex-1">
            <div class="animate-fade-in mt-2">{{ $slot }}</div>
        </div>
    </div>
</div>
