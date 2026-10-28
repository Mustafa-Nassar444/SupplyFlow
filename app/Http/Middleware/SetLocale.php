<?php

namespace App\Http\Middleware;

use App\Services\Languages\LanguageCatalog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $catalog = app(LanguageCatalog::class);
        // Re-read per HTTP request, including long-running workers and feature tests.
        $catalog->forget();
        $locale = $request->session()->get('locale');
        $selected = $catalog->active()->firstWhere('code', $locale) ?? $catalog->default();
        app()->setLocale($selected->code);
        if ($locale !== null && $locale !== $selected->code) {
            $request->session()->put('locale', $selected->code);
        }
        view()->share('uiLanguages', $catalog->active());
        view()->share('uiDirection', $selected->direction);

        return $next($request);
    }
}
