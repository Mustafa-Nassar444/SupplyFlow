<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLanguageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('language')) ?? false;
    }

    public function rules(): array
    {
        return ['field' => ['required', 'in:active,default'], 'value' => ['required', 'boolean']];
    }

    public function messages(): array
    {
        return array_fill_keys(['field.required', 'field.in', 'value.required', 'value.boolean'], __('languages.invalid'));
    }
}
