<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AjaxExampleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'items' => ['required', 'array', 'size:1'],
            'items.0.quantity' => ['required', 'integer', 'between:1,100'],
            'attachment' => ['nullable', 'file', 'max:1024'],
        ];
    }

    public function messages(): array
    {
        return array_fill_keys([
            'name.required', 'name.string', 'name.max', 'items.required', 'items.array', 'items.size',
            'items.0.quantity.required', 'items.0.quantity.integer', 'items.0.quantity.between',
            'attachment.file', 'attachment.max', 'attachment.uploaded',
        ], __('ui.validation'));
    }
}
