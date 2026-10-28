<?php

namespace App\Http\Requests\dashboard;

use App\Http\Traits\TranslationTrait;
use App\Models\Category;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CategoryRequest extends FormRequest
{
    use TranslationTrait;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $category = $this->route('category');

        return $category instanceof Category
            ? ($this->user()?->can('update', $category) ?? false)
            : ($this->user()?->can('create', Category::class) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = $this->translationRules(['name' => ['required', 'string', 'max:255']]);
        $rules['name'] = ['missing'];
        foreach (array_keys($this->all()) as $field) {
            if (str_starts_with($field, 'name_') && ! array_key_exists($field, $rules)) {
                $rules[$field] = ['missing'];
            }
        }

        return $rules;
    }

    public function messages(): array
    {
        $messages = ['name.missing' => __('categories.validation.format')];
        foreach ($this->rules() as $field => $rules) {
            if (! str_starts_with($field, 'name_')) {
                continue;
            }
            foreach (['required', 'string', 'max', 'prohibited'] as $rule) {
                $messages[$field.'.'.$rule] = __('categories.validation.'.$rule);
            }
            $messages[$field.'.missing'] = __('categories.validation.prohibited');
        }

        return $messages;
    }

    public function attributes(): array
    {
        return $this->activeLanguages()->mapWithKeys(fn ($language) => [
            'name_'.$language->code => __('ui.name').' — '.$language->name,
        ])->all();
    }
}
