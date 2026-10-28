<?php

namespace App\Services\Languages;

use App\Models\Language;
use Astrotomic\Translatable\Locales;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LanguageCatalog
{
    private ?Collection $languages = null;

    private ?int $defaultId = null;

    private bool $installed = false;

    public function all(): Collection
    {
        if ($this->languages === null) {
            $this->installed = Schema::hasTable('languages') && Schema::hasTable('language_settings');
            if ($this->installed) {
                $this->languages = Language::orderBy('sort_order')->orderBy('code')->get();
                $this->defaultId = DB::table('language_settings')->where('id', 1)->value('default_language_id');
                if ($this->languages->isEmpty()) {
                    $this->installed = false;
                    $this->languages = collect([new Language(['name' => 'English', 'code' => 'en', 'direction' => 'ltr', 'active' => true]), new Language(['name' => 'العربية', 'code' => 'ar', 'direction' => 'rtl', 'active' => true])]);
                }
            } else {
                // Read-only compatibility before migration; no settings are writable.
                $this->languages = collect([
                    new Language(['name' => 'العربية', 'code' => 'ar', 'direction' => 'rtl', 'active' => true]),
                    new Language(['name' => 'English', 'code' => 'en', 'direction' => 'ltr', 'active' => true]),
                ]);
            }
        }

        return $this->languages;
    }

    public function active(): Collection
    {
        return $this->all()->filter(fn (Language $language) => $language->active && app(LanguageReadiness::class)->ready($language->code, $language->direction))->values();
    }

    public function codes(): array
    {
        return $this->active()->pluck('code')->all();
    }

    public function default(): Language
    {
        $active = $this->active();
        $language = $this->installed ? $active->firstWhere('id', $this->defaultId) : $active->firstWhere('code', config('app.locale'));

        return $language ?? $active->first() ?? throw new \RuntimeException('No ready active language. Initialize LanguagesSeeder.');
    }

    public function defaultId(): ?int
    {
        $this->all();

        return $this->defaultId;
    }

    public function snapshot(): array
    {
        return $this->all()->map(fn (Language $language) => [
            'id' => $language->id, 'code' => $language->code, 'active' => $language->active,
            'default' => $language->id === $this->defaultId, 'direction' => $language->direction,
        ])->all();
    }

    public function options(): array
    {
        return $this->active()->map(fn (Language $language) => ['code' => $language->code, 'name' => $language->name])->all();
    }

    public function forget(): void
    {
        $this->languages = null;
        $this->defaultId = null;
    }

    public function synchronizeAstrotomic(): void
    {
        config(['translatable.locales' => $this->codes()]);
        if (app()->resolved(Locales::class)) {
            app(Locales::class)->load();
        }
    }
}
