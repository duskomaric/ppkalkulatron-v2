@props(['setup', 'dismissible' => true])

{{-- Redoslijed koraka do prvog računa; stanje se čita iz same aplikacije. --}}
@php
    $steps = $setup->steps();
    $done = count($steps) - $setup->remaining();
@endphp

<div class="space-y-4 animate-fade-in">
    <x-section-block variant="card">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="text-lg font-black italic tracking-tight text-[var(--color-text-main)]">
                    {{ $setup->isComplete() ? 'Sve je spremno' : 'Podesite aplikaciju za rad' }}
                </p>
                <p class="mt-1 text-[11px] leading-relaxed text-[var(--color-text-dim)]">
                    {{ $setup->isComplete()
                        ? 'Svi koraci su završeni — možete izdati račun.'
                        : 'Prije prvog računa treba podesiti ono ispod. Svaki korak vodi na svoj ekran, a kvačica se pojavi sama.' }}
                </p>
            </div>

            <span class="shrink-0 rounded-xl border border-primary/30 bg-primary/10 px-3 py-1.5 text-[11px] font-black text-primary">
                {{ $done }}/{{ count($steps) }}
            </span>
        </div>

        <div class="h-1.5 w-full overflow-hidden rounded-full bg-[var(--color-surface-hover)]">
            <div class="h-full rounded-full bg-primary transition-all" style="width: {{ round($done / max(1, count($steps)) * 100) }}%"></div>
        </div>

        <div class="space-y-2">
            @foreach ($steps as $index => $step)
                <a href="{{ $step['route'] }}"
                   class="flex items-center gap-3 rounded-xl border p-3 transition-all
                          {{ $step['done']
                              ? 'border-[var(--color-border)] bg-[var(--color-bg)]'
                              : 'border-primary/30 bg-primary/5 hover:border-primary/60' }}">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-xs font-black
                                 {{ $step['done'] ? 'bg-emerald-500/10 text-emerald-500' : 'bg-primary/15 text-primary' }}">
                        @if ($step['done'])
                            <x-icon name="check" class="h-4 w-4" />
                        @else
                            {{ $index + 1 }}
                        @endif
                    </span>

                    <span class="min-w-0 flex-1">
                        <span class="block text-[13px] font-black {{ $step['done'] ? 'text-[var(--color-text-dim)] line-through' : 'text-[var(--color-text-main)]' }}">
                            {{ $step['title'] }}
                        </span>
                        <span class="mt-0.5 block text-[11px] leading-relaxed text-[var(--color-text-dim)]">{{ $step['description'] }}</span>
                    </span>

                    @unless ($step['done'])
                        <span class="hidden shrink-0 text-[10px] font-black uppercase tracking-widest text-primary sm:block">{{ $step['action'] }}</span>
                        <x-icon name="chevron-right" class="h-4 w-4 shrink-0 text-primary" />
                    @endunless
                </a>
            @endforeach
        </div>

        @if ($dismissible)
            <form method="POST" action="{{ route('setup.dismiss') }}">
                @csrf
                <x-button variant="ghost" class="w-full !py-3 !text-[11px] !uppercase !tracking-[0.2em] !font-black">
                    Sakrij vodič
                </x-button>
            </form>

            <p class="text-center text-[10px] text-[var(--color-text-dim)]">
                Uvijek se može vratiti u Podešavanja → Početno podešavanje.
            </p>
        @endif
    </x-section-block>

    <x-section-block variant="card">
        <x-section-header icon="star" title="Preporučeno" subtitle="Nije uslov za račun, ali olakšava rad." />

        <div class="grid gap-2 sm:grid-cols-3">
            @foreach ($setup->recommended() as $item)
                <a href="{{ $item['route'] }}"
                   class="flex items-center gap-2 rounded-xl border border-[var(--color-border)] bg-[var(--color-bg)] p-3 text-[11px] font-bold transition-colors hover:border-primary/40">
                    <x-icon :name="$item['done'] ? 'check' : 'plus'" class="h-4 w-4 shrink-0 {{ $item['done'] ? 'text-emerald-500' : 'text-primary' }}" />
                    <span class="min-w-0 {{ $item['done'] ? 'text-[var(--color-text-dim)]' : 'text-[var(--color-text-main)]' }}">{{ $item['title'] }}</span>
                </a>
            @endforeach
        </div>
    </x-section-block>
</div>
