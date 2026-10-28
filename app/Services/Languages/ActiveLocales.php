<?php

namespace App\Services\Languages;

use Astrotomic\Translatable\Locales;

class ActiveLocales extends Locales
{
    public function load(): void
    {
        $codes = app(LanguageCatalog::class)->codes();
        $this->config->set('translatable.locales', $codes);
        $this->locales = array_combine($codes, $codes) ?: [];
    }

    public function all(): array
    {
        $this->load();

        return parent::all();
    }

    public function has(string $locale): bool
    {
        $this->load();

        return parent::has($locale);
    }

    public function get(string $locale): ?string
    {
        $this->load();

        return parent::get($locale);
    }
}
