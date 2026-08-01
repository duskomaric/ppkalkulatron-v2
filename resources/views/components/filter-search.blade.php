@props(['value' => '', 'placeholder' => 'Pretraži...'])

<x-form-input variant="filter" name="q" type="search" :value="$value" :placeholder="$placeholder" icon="search"
              aria-label="Pretraga"
              x-on:input.debounce.600ms="$el.form.requestSubmit()"
              x-on:keydown.enter.prevent="$el.form.requestSubmit()" />
