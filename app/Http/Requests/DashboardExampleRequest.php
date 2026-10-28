<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DashboardExampleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'category' => ['required', 'in:sample,other'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'acknowledge' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return array_fill_keys(['name.required', 'name.string', 'name.max', 'email.required', 'email.email', 'email.max', 'category.required', 'category.in', 'notes.string', 'notes.max', 'acknowledge.accepted'], __('ui.validation'));
    }
}
