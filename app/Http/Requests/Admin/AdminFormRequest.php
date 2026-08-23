<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Shared base for the panel's write requests.
 *
 * Authorisation is simply "signed in": there is one staff role, and the routes
 * already sit behind `auth`. Introduce a Gate here if roles ever land.
 */
abstract class AdminFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Rules for translations[<locale>][<column>].
     *
     * Every translated field is optional — a page may legitimately ship in one
     * language first, and HasTranslations falls back per field. List columns
     * arrive as textarea text and are validated as strings, then split on save.
     *
     * @param  list<string>  $fields
     */
    protected function translationRules(array $fields): array
    {
        $rules = ['translations' => ['nullable', 'array']];

        foreach (translation_locales() as $locale) {
            $rules["translations.{$locale}"] = ['nullable', 'array'];

            foreach ($fields as $field) {
                $rules["translations.{$locale}.{$field}"] = ['nullable', 'string'];
            }
        }

        return $rules;
    }

    /** Localised field names, so error messages read "নাম" rather than "name". */
    public function attributes(): array
    {
        $attributes = [];

        foreach ((array) __('admin.fields') as $field => $label) {
            $attributes[$field] = $label;

            foreach (translation_locales() as $locale) {
                $attributes["translations.{$locale}.{$field}"] = $label.' ('.$locale.')';
            }
        }

        return $attributes;
    }
}
