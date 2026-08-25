<?php

namespace App\Http\Requests\Admin;

use App\Support\StaffRoles;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Shared base for the panel's write requests.
 *
 * Signed in, and holding a role that includes the section being written to.
 * The `staff` middleware already answers the second half on the way in, so
 * this is deliberately the same question asked twice — a write is the half of
 * the panel that cannot be undone by navigating away, and a route added
 * outside the guarded group would otherwise be authorised by nothing at all.
 */
abstract class AdminFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        $section = StaffRoles::sectionForRoute($this->route()?->getName());

        return auth()->check()
            && ($section === null || auth()->user()->canReach($section));
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
