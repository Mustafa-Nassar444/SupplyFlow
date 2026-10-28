<?php

namespace App\Http\Traits;

use App\Services\Languages\LanguageCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

trait TranslationTrait
{
    public function activeLanguages(): Collection
    {
        return app(LanguageCatalog::class)->active();
    }

    public function activeLanguageCodes(): array
    {
        return app(LanguageCatalog::class)->codes();
    }

    public function translationRules(array $fields): array
    {
        $rules = [];
        foreach ($this->activeLanguageCodes() as $code) {
            foreach ($fields as $field => $rule) {
                $rules[$field.'_'.$code] = $rule;
            }
        }

        return $rules;
    }

    public function assertActiveTranslationInputs(Request $request, array $fields): void
    {
        $codes = $this->activeLanguageCodes();
        foreach (array_keys($request->all()) as $key) {
            foreach ($fields as $field) {
                if (str_starts_with($key, $field.'_') && ! in_array(substr($key, strlen($field) + 1), $codes, true)) {
                    throw ValidationException::withMessages([$key => __('languages.inactive_payload')]);
                }
            }
        }
    }

    // Preserve the existing flat field_locale signature and explicit caller-controlled save.
    public function storeTranslates($model, $request, $fields)
    {
        $this->assertActiveTranslationInputs($request, $fields);
        foreach ($this->activeLanguageCodes() as $code) {
            foreach ($fields as $field) {
                if ($request->has($field.'_'.$code)) {
                    $model->translateOrNew($code)->$field = $request->input($field.'_'.$code);
                }
            }
        }
    }
}
