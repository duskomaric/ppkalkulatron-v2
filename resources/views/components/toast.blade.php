{{--
    v1 Toast: klizne odozgo, sam se sklanja, sa trakom koja odbrojava.
    Sluša `app-flash` — poruke iz radnji koje ne osvježavaju stranicu.
--}}
<div x-data="{
        shown: false,
        message: '',
        type: 'success',
        timer: null,
        show(detail) {
            this.message = typeof detail === 'string' ? detail : detail.message;
            this.type = (typeof detail === 'object' && detail.type) ? detail.type : 'success';
            this.shown = true;
            clearTimeout(this.timer);
            this.timer = setTimeout(() => (this.shown = false), 5000);
        },
     }"
     x-on:app-flash.window="show($event.detail)"
     x-cloak x-show="shown"
     class="fixed top-6 left-1/2 -translate-x-1/2 z-[1200] w-[calc(100%-2.5rem)] max-w-[420px] transition-all duration-500"
     x-transition:enter-start="-translate-y-6 opacity-0 scale-95"
     x-transition:leave-end="-translate-y-6 opacity-0 scale-95">
    <div class="relative overflow-hidden backdrop-blur-2xl bg-zinc-900/80 border rounded-2xl shadow-[0_20px_50px_rgba(0,0,0,0.4)]"
         :class="type === 'error' ? 'bg-rose-500/10 border-rose-500/20' : 'bg-emerald-500/10 border-emerald-500/20'">
        <div class="flex items-start justify-between gap-4 p-4">
            <div class="flex items-start gap-4 min-w-0">
                <div class="h-10 w-10 min-w-[2.5rem] rounded-xl flex items-center justify-center shadow-lg shrink-0 ring-4 ring-black/10"
                     :class="type === 'error' ? 'bg-rose-500' : 'bg-emerald-500'">
                    <x-icon name="check" class="h-4 w-4 text-white" x-show="type !== 'error'" />
                    <x-icon name="x" class="h-4 w-4 text-white" x-show="type === 'error'" x-cloak />
                </div>
                <div class="min-w-0 pt-0.5">
                    <span class="block text-white/40 font-bold text-[10px] uppercase tracking-[0.2em] mb-1"
                          x-text="type === 'error' ? 'Greška' : 'Uspjeh'"></span>
                    <p class="text-[14px] font-medium leading-relaxed text-zinc-100 break-words" x-text="message"></p>
                </div>
            </div>

            <button type="button" x-on:click="shown = false" aria-label="Zatvori"
                    class="p-2 -mr-1 rounded-xl text-zinc-500 hover:text-white hover:bg-white/5 transition-all shrink-0 cursor-pointer">
                <x-icon name="x" class="h-4 w-4" />
            </button>
        </div>

        <div class="absolute bottom-0 left-0 right-0 h-[3px] bg-white/5">
            <div class="h-full opacity-60" :class="type === 'error' ? 'bg-rose-500' : 'bg-emerald-500'"></div>
        </div>
    </div>
</div>
