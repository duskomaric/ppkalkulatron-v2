@props(['id', 'title', 'icon' => 'info'])

{{-- Sidro mora imati razmak odozgo jer je zaglavlje ljepljivo. --}}
<section id="{{ $id }}" class="scroll-mt-24 space-y-3">
    <div class="flex items-center gap-2">
        <div class="h-8 w-8 bg-primary/10 text-primary rounded-lg flex items-center justify-center shrink-0">
            <x-icon :name="$icon" class="h-4 w-4" />
        </div>
        <h2 class="text-lg font-black text-[var(--color-text-main)] tracking-tight italic">{{ $title }}</h2>
    </div>

    <div class="p-4 sm:p-5 rounded-2xl border border-[var(--color-border)] bg-[var(--color-surface)] space-y-3
                [&_p]:text-sm [&_p]:text-[var(--color-text-muted)] [&_p]:leading-relaxed
                [&_strong]:text-[var(--color-text-main)] [&_strong]:font-bold
                [&_li]:text-sm [&_li]:text-[var(--color-text-muted)] [&_li]:leading-relaxed">
        {{ $slot }}
    </div>
</section>
