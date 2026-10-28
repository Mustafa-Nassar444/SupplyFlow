<?php

namespace App\Providers;

use App\Services\Languages\ActiveLocales;
use App\Services\Languages\LanguageCatalog;
use App\Services\Languages\LanguageReadiness;
use Astrotomic\Translatable\Locales;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(LanguageCatalog::class);
        $this->app->scoped(LanguageReadiness::class);
        $this->app->scoped(Locales::class, fn ($app) => new ActiveLocales($app['config'], $app['translator']));
        $this->app->scoped('translatable.locales', fn ($app) => $app->make(Locales::class));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
