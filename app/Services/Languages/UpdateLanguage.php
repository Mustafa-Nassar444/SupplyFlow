<?php

namespace App\Services\Languages;

use App\Models\Language;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateLanguage
{
    public function execute(int $id, string $field, bool $value): array
    {
        DB::transaction(function () use ($id, $field, $value) {
            $settings = DB::table('language_settings')->where('id', 1)->lockForUpdate()->first();
            abort_unless($settings, 503, __('languages.uninitialized'));
            $language = Language::whereKey($id)->lockForUpdate()->firstOrFail();
            $reject = fn (string $key) => throw ValidationException::withMessages([$field => __($key)]);
            if ($field === 'default') {
                if (! $value) {
                    $reject('languages.keep_default');
                }
                if (! $language->active) {
                    $reject('languages.activate_first');
                }
                if (! app(LanguageReadiness::class)->ready($language->code, $language->direction)) {
                    $reject('languages.not_ready');
                }
                DB::table('language_settings')->where('id', 1)->update(['default_language_id' => $id]);
            } else {
                if (! $value && (int) $settings->default_language_id === $id) {
                    $reject('languages.choose_default');
                }
                if ($value && ! app(LanguageReadiness::class)->ready($language->code, $language->direction)) {
                    $reject('languages.not_ready');
                }
                $language->update(['active' => $value]);
            }
        }, 3);
        $catalog = app(LanguageCatalog::class);
        $catalog->forget();
        $catalog->synchronizeAstrotomic();

        return $catalog->snapshot();
    }
}
