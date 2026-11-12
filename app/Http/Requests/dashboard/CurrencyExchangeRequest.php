<?php

namespace App\Http\Requests\dashboard;

use App\Http\Traits\TranslationTrait;
use App\Models\CurrencyExchange;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CurrencyExchangeRequest extends FormRequest
{

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $currency = $this->route('currency_exchange');

        return $currency instanceof CurrencyExchange
            ? ($this->user()?->can('update', $currency) ?? false)
            : ($this->user()?->can('create', CurrencyExchange::class) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules['currency_id'] = ['required', 'exists:currencies,id,is_active,1,is_main,false'];
        $rules['exc_prc'] = ['required', 'numeric', 'min:0'];


        return $rules;
    }

    public function messages(): array
    {
        $messages['currency_id.required'] = __('validation.currencies-exchange.currency_id');
        $messages['currency_id.exists'] = __('validation.currencies-exchange.currency_id');
        $messages['exc_prc.required'] = __('validation.currencies-exchange.exc_prc');
        $messages['exc_prc.numeric'] = __('validation.currencies-exchange.exc_prc');
        $messages['exc_prc.min'] = __('validation.currencies-exchange.exc_prc');

        return $messages;
    }

    public function attributes(): array
    {
        return [
            'currency_id' => __('ui.currencies.currency'),
            'exc_prc' => __('ui.currencies.exc_prc'),
        ];
    }
}
