@props(['ariaLabel' => null, 'name', 'value' => ''])

<x-form-input variant="filter" type="date" :name="$name" :value="$value" :aria-label="$ariaLabel ?? 'Datum'" auto-submit
              class="min-w-[120px] !px-3 !text-xs !tracking-[0.12em]" />
