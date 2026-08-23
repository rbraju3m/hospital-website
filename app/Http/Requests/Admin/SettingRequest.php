<?php

namespace App\Http\Requests\Admin;

use App\Models\Setting;

class SettingRequest extends AdminFormRequest
{
    public function rules(): array
    {
        $rules = [
            'values' => ['required', 'array'],
            'values.*' => ['nullable', 'string', 'max:2000'],
            'translations' => ['nullable', 'array'],
        ];

        // Only the label-ish settings are translatable; a phone number or a
        // social URL is the same string in every locale.
        foreach (translation_locales() as $locale) {
            $rules["translations.{$locale}"] = ['nullable', 'array'];

            foreach (Setting::TRANSLATABLE_KEYS as $key) {
                $rules["translations.{$locale}.{$key}"] = ['nullable', 'string', 'max:2000'];
            }
        }

        return $rules;
    }
}
