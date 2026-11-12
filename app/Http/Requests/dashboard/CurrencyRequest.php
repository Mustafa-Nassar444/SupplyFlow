<?php

namespace App\Http\Requests\dashboard;

use App\Http\Traits\TranslationTrait;
use App\Models\Currency;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CurrencyRequest extends FormRequest
{
    use TranslationTrait;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $currency = $this->route('currency');

        return $currency instanceof Currency
            ? ($this->user()?->can('update', $currency) ?? false)
            : ($this->user()?->can('create', Currency::class) ?? false);
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
        $rules['symbol']= ['nullable','string','max:10'];
        $rules['is_active']= ['nullable','boolean'];
        $rules['is_main']= ['nullable','boolean'];
        foreach (array_keys($this->all()) as $field) {
            if (str_starts_with($field, 'name_') && ! array_key_exists($field, $rules)) {
                $rules[$field] = ['missing'];
            }
        }

        return $rules;
    }

    public function messages(): array
    {
        $messages = ['name.missing' => __('validation.format')];
        $messages['symbol.string'] = __('ui.currencies.validation.symbol');
        $messages['symbol.max'] = __('ui.currencies.validation.symbol');
        $messages['is_active.boolean'] = __('ui.currencies.validation.boolean');
        $messages['is_main.boolean'] = __('ui.currencies.validation.boolean');
        foreach ($this->rules() as $field => $rules) {
            if (! str_starts_with($field, 'name_')) {
                continue;
            }
            foreach (['required', 'string', 'max', 'prohibited'] as $rule) {
                $messages[$field.'.'.$rule] = __('validation.'.$rule);
            }
            $messages[$field.'.missing'] = __('validation.prohibited');
        }

        return $messages;
    }

    public function attributes(): array
    {
        return $this->activeLanguages()->mapWithKeys(fn ($language) => [
            'name_'.$language->code => __('ui.name').' â€” '.$language->name,
        ])->all();
    }
}
