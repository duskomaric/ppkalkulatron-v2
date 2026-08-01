@props(['value' => '', 'placeholder' => 'Pretraga…'])

<form method="GET" class="mb-4">
    <x-form-input name="q" type="search" :value="$value" :placeholder="$placeholder" icon="search"
                  class="!h-11 !bg-[var(--color-glass)] !backdrop-blur-xl focus:!border-primary/40" />
</form>
