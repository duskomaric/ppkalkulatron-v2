@extends('layouts.app')

@section('title', 'Profil')

@section('content')
    {{--
        Prati v1 profile stranicu: kartica korisnika, izbor teme, radnje naloga i
        verzija. Umjesto odjave stoji zaključavanje — v2 nema naloge, samo PIN.
    --}}
    <div class="space-y-6 pb-20 max-w-3xl" x-data="{ editDrawer: {{ $errors->any() ? 'true' : 'false' }} }">
        <div class="p-5 bg-[var(--color-surface)] rounded-3xl border border-[var(--color-border)] relative overflow-hidden group">
            <div class="absolute top-0 right-0 p-6 opacity-5">
                <x-icon name="user" class="h-16 w-16 text-[var(--color-text-dim)]" />
            </div>
            <div class="flex items-center gap-4 relative z-10">
                <div class="h-16 w-16 bg-primary rounded-2xl flex items-center justify-center text-white font-black text-2xl shadow-glow-primary">
                    {{ $user->initials() }}
                </div>
                <div class="min-w-0">
                    <h2 class="text-xl font-black text-[var(--color-text-main)] tracking-tighter italic truncate">
                        {{ $user->fullName() ?: 'Korisnik' }}
                    </h2>
                    @if ($user->email)
                        <div class="flex items-center gap-1.5 text-[var(--color-text-dim)] mt-0.5">
                            <x-icon name="mail" class="h-3 w-3" />
                            <p class="text-[11px] font-bold tracking-tight truncate">{{ $user->email }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="space-y-3">
            <x-section-header icon="cog" title="Postavke teme" class="px-1" />

            <div class="bg-[var(--color-surface)] rounded-3xl border border-[var(--color-border)] p-1 flex items-center gap-1"
                 x-data x-init="$store.theme.init()">
                @foreach ([['light', 'Svijetla', 'sun'], ['dark', 'Tamna', 'moon'], ['system', 'Sistemska', 'monitor']] as [$value, $label, $icon])
                    <button type="button" x-on:click="$store.theme.set('{{ $value }}')"
                            class="flex-1 flex flex-col items-center gap-2 py-4 rounded-2xl transition-all cursor-pointer"
                            :class="$store.theme.choice === '{{ $value }}'
                                ? 'bg-primary text-white shadow-glow-primary'
                                : 'text-[var(--color-text-dim)] hover:text-[var(--color-text-main)] hover:bg-[var(--color-surface-hover)]'">
                        <x-icon :name="$icon" class="h-5 w-5" />
                        <span class="text-[9px] font-black uppercase tracking-widest">{{ $label }}</span>
                    </button>
                @endforeach
            </div>
        </div>

        <div class="space-y-3">
            <x-section-header icon="building" title="Nalog" class="px-1" />

            <div class="bg-[var(--color-surface)] rounded-3xl border border-[var(--color-border)] overflow-hidden">
                <button type="button" x-on:click="editDrawer = true"
                        class="w-full flex items-center justify-between p-4 hover:bg-[var(--color-surface-hover)] transition-all text-left cursor-pointer">
                    <div class="flex items-center gap-3">
                        <div class="h-8 w-8 bg-[var(--color-border)] rounded-lg flex items-center justify-center text-[var(--color-text-dim)]">
                            <x-icon name="user" class="h-4 w-4" />
                        </div>
                        <span class="text-[13px] font-bold text-[var(--color-text-muted)]">Uredi podatke</span>
                    </div>
                    <x-icon name="chevron-right" class="h-4 w-4 text-[var(--color-text-dim)]" />
                </button>

                <div class="h-[1px] w-full bg-[var(--color-border)]"></div>

                <form method="POST" action="{{ route('unlock.destroy') }}">
                    @csrf
                    <button type="submit"
                            class="w-full flex items-center justify-between p-4 hover:bg-red-500/10 transition-all text-left group cursor-pointer">
                        <div class="flex items-center gap-3">
                            <div class="h-8 w-8 bg-red-500/10 rounded-lg flex items-center justify-center text-red-500 group-hover:bg-red-500 group-hover:text-white transition-all">
                                <x-icon name="lock" class="h-4 w-4" />
                            </div>
                            <span class="text-[13px] font-bold text-red-500">Zaključaj aplikaciju</span>
                        </div>
                        <x-icon name="chevron-right" class="h-4 w-4 text-[var(--color-text-dim)]" />
                    </button>
                </form>
            </div>
        </div>

        <div class="text-center pt-4">
            <p class="text-[8px] font-black text-[var(--color-text-dim)] uppercase tracking-[0.3em]">
                ppKalkulatron {{ config('app.version', 'v2') }}
            </p>
        </div>

        <x-drawer title="Uredi podatke" state="editDrawer">
            <form method="POST" action="{{ route('profile.update') }}" class="space-y-4">
                @csrf
                @method('PUT')

                <x-form-input label="Ime" name="first_name" :value="$user->first_name" required />
                <x-form-input label="Prezime" name="last_name" :value="$user->last_name" />
                <x-form-input label="Email" name="email" type="email" :value="$user->email" />

                <div class="flex flex-col gap-2 pt-2">
                    <button type="submit"
                            class="w-full py-3.5 bg-primary text-white rounded-xl font-black text-[11px] uppercase tracking-[0.2em] shadow-glow-primary hover:scale-[1.02] active:scale-95 transition-all cursor-pointer">
                        Sačuvaj izmjene
                    </button>
                    <x-drawer-secondary-button label="Odustani" x-on:click="editDrawer = false" />
                </div>
            </form>
        </x-drawer>
    </div>
@endsection
