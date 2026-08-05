<?php

namespace App\Http\Requests;

use App\Settings\MenuSettings;
use App\Support\Brand;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMenuSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $keys = array_keys(MenuSettings::modules());

        return [
            'menu_modules' => ['array', 'max:4'],
            'menu_modules.*' => [Rule::in($keys)],
            'drawer_modules' => ['array'],
            'drawer_modules.*' => [Rule::in($keys), 'distinct'],
            'max_menu_items' => ['required', 'integer', 'min:1', 'max:4'],
            // Boja se bira iz palete; ručno poslata vrijednost izvan nje se odbija.
            'primary_color' => ['sometimes', 'required', Rule::in(array_keys(Brand::palette()))],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'menu_modules' => array_filter((array) $this->input('menu_modules', [])),
            'drawer_modules' => array_filter((array) $this->input('drawer_modules', [])),
        ]);
    }

    public function attributes(): array
    {
        return ['menu_modules' => 'moduli u meniju', 'primary_color' => 'boja aplikacije'];
    }
}
