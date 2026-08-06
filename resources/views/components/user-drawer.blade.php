<x-drawer title="Moj nalog" state="userDrawer">
    <div class="flex items-center gap-4 p-4 bg-[var(--color-surface)] rounded-2xl mb-4 border border-[var(--color-border)] relative overflow-hidden">
        <div class="h-12 w-12 bg-primary rounded-xl flex items-center justify-center text-white font-black text-lg shadow-glow-primary shrink-0 z-10">
            {{ $user->initials() }}
        </div>
        <div class="z-10 min-w-0">
            <p class="font-black text-base text-[var(--color-text-main)] leading-tight italic tracking-tight truncate">
                {{ $user->fullName() ?: 'Korisnik' }}
            </p>
            @if ($user->email)
                <p class="text-[10px] text-[var(--color-text-muted)] font-bold uppercase tracking-wider mt-0.5 truncate">
                    {{ $user->email }}
                </p>
            @endif
        </div>
    </div>

    <div class="space-y-1.5">
        <a href="{{ route('profile.edit') }}"
           class="w-full text-left p-3.5 text-xs font-black uppercase tracking-widest text-[var(--color-text-muted)] hover:text-[var(--color-text-main)] hover:bg-[var(--color-surface-hover)] rounded-xl transition-all flex items-center gap-3 border border-transparent hover:border-[var(--color-border)] group cursor-pointer">
            <div class="h-7 w-7 bg-[var(--color-border)] rounded-lg flex items-center justify-center text-[var(--color-text-dim)] group-hover:text-primary transition-colors">
                <x-icon name="user" class="h-4 w-4" />
            </div>
            Moj profil
        </a>

        <a href="{{ route('help') }}"
           class="w-full text-left p-3.5 text-xs font-black uppercase tracking-widest text-[var(--color-text-muted)] hover:text-[var(--color-text-main)] hover:bg-[var(--color-surface-hover)] rounded-xl transition-all flex items-center gap-3 border border-transparent hover:border-[var(--color-border)] group cursor-pointer">
            <div class="h-7 w-7 bg-[var(--color-border)] rounded-lg flex items-center justify-center text-[var(--color-text-dim)] group-hover:text-primary transition-colors">
                <x-icon name="info" class="h-4 w-4" />
            </div>
            Pomoć
        </a>

        @if ($pinEnabled)
        <form method="POST" action="{{ route('unlock.destroy') }}">
            @csrf
            <button type="submit"
                    class="w-full text-left p-3.5 text-xs font-black uppercase tracking-widest text-red-500/80 hover:text-red-500 hover:bg-red-400/5 rounded-xl transition-all flex items-center gap-3 border border-transparent hover:border-red-500/20 group cursor-pointer">
                <div class="h-7 w-7 bg-red-500/10 rounded-lg flex items-center justify-center text-red-500 transition-colors">
                    <x-icon name="lock" class="h-4 w-4" />
                </div>
                Zaključaj
            </button>
        </form>
        @endif
    </div>
</x-drawer>
