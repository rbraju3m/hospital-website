<?php

namespace App\Http\Requests\Admin;

use App\Http\Controllers\Web\DiagnosticController;
use Illuminate\Validation\Rule;

class DiagnosticTestRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return array_merge([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash',
                Rule::unique('diagnostic_tests', 'slug')->ignore($this->route('diagnostic'))],
            // The counter's order code, quoted back by patients from a
            // prescription — an identifier, so it stays Latin in both locales.
            'code' => ['nullable', 'string', 'max:32',
                Rule::unique('diagnostic_tests', 'code')->ignore($this->route('diagnostic'))],
            'category' => ['required', Rule::in(DiagnosticController::CATEGORIES)],
            'summary' => ['nullable', 'string', 'max:2000'],
            'preparation' => ['nullable', 'string', 'max:2000'],
            'sample_type' => ['nullable', 'string', 'max:255'],
            'report_time' => ['nullable', 'string', 'max:255'],
            'price' => ['required', 'integer', 'min:0', 'max:10000000'],
            'discount_price' => ['nullable', 'integer', 'min:0', 'max:10000000', 'lt:price'],
            'is_home_collection' => ['boolean'],
            'is_featured' => ['boolean'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ], $this->translationRules(['name', 'summary', 'preparation', 'sample_type', 'report_time']));
    }
}
