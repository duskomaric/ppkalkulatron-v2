@props(['value'])

@php
    $selectedTemplate = old('template', $value);
    $templates = \App\Enums\DocumentTemplate::cases();
@endphp

<fieldset class="space-y-3" x-data="{ template: @js($selectedTemplate) }">
    <legend class="text-sm font-bold text-[var(--color-text-main)]">Predložak računa</legend>
    <p class="text-xs leading-5 text-[var(--color-text-dim)]">Izaberite izgled koji će se koristiti na novim računima.</p>

    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
        @foreach ($templates as $template)
            @php
                $previewClasses = match ($template) {
                    \App\Enums\DocumentTemplate::Classic => 'bg-white text-slate-700 border-slate-200',
                    \App\Enums\DocumentTemplate::Modern, \App\Enums\DocumentTemplate::Protocol => 'bg-blue-50 text-blue-700 border-blue-200',
                    \App\Enums\DocumentTemplate::Minimal => 'bg-stone-50 text-stone-700 border-stone-200',
                    \App\Enums\DocumentTemplate::Standard, \App\Enums\DocumentTemplate::Kernel, \App\Enums\DocumentTemplate::Shell => 'bg-orange-50 text-orange-700 border-orange-200',
                    \App\Enums\DocumentTemplate::Programmer, \App\Enums\DocumentTemplate::Editor => 'bg-violet-950 text-violet-100 border-violet-800',
                    \App\Enums\DocumentTemplate::Blueprint, \App\Enums\DocumentTemplate::TerminalLight => 'bg-teal-50 text-teal-800 border-teal-200',
                    \App\Enums\DocumentTemplate::Terminal => 'bg-[#0a0f0d] text-lime-100 border-lime-800',
                    \App\Enums\DocumentTemplate::Signal => 'bg-fuchsia-950 text-pink-100 border-pink-700',
                    \App\Enums\DocumentTemplate::OpsConsole => 'bg-[#07111d] text-cyan-100 border-cyan-900',
                    \App\Enums\DocumentTemplate::Workstation => 'bg-indigo-50 text-indigo-800 border-indigo-200',
                };
            @endphp
            <label class="group relative block cursor-pointer">
                <input type="radio" name="template" value="{{ $template->value }}" x-model="template" class="peer sr-only" @checked($selectedTemplate === $template->value)>
                <span class="block overflow-hidden rounded-xl border-2 border-[var(--color-border)] bg-[var(--color-surface)] p-2 transition peer-checked:border-primary peer-checked:ring-2 peer-checked:ring-primary/20 group-hover:border-primary/50">
                    <span class="{{ $previewClasses }} block aspect-[1.35] overflow-hidden rounded-lg border p-2 font-mono text-[7px] leading-tight">
                        <span class="mb-2 flex items-center gap-1 opacity-70"><i class="h-1.5 w-1.5 rounded-full bg-current"></i><i class="h-1.5 w-1.5 rounded-full bg-current"></i><i class="h-1.5 w-1.5 rounded-full bg-current"></i></span>
                        <span class="mb-1 block h-2 w-4/5 rounded bg-current opacity-85"></span>
                        <span class="mb-2 block h-1 w-3/5 rounded bg-current opacity-35"></span>
                        <span class="grid grid-cols-[1fr_auto] gap-x-1 border-y border-current py-1 opacity-75"><span>stavka</span><span>iznos</span><span class="mt-1 h-1 w-10 rounded bg-current opacity-40"></span><span class="mt-1 h-1 w-6 rounded bg-current opacity-60"></span></span>
                        <span class="mt-2 ml-auto block h-3 w-2/5 rounded bg-current opacity-90"></span>
                    </span>
                    <span class="mt-2 flex items-center justify-between gap-2 px-0.5 text-xs font-bold text-[var(--color-text-main)]">
                        {{ $template->label() }}
                        <x-icon name="check" class="h-3.5 w-3.5 text-primary opacity-0 transition peer-checked:opacity-100" />
                    </span>
                </span>
            </label>
        @endforeach
    </div>
    @error('template')
        <p class="text-xs font-bold text-[var(--color-error)]">{{ $message }}</p>
    @enderror
</fieldset>
