<?php

namespace App\Http\Controllers;

use App\Services\Languages\LanguageCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function __invoke(Request $request, LanguageCatalog $catalog): RedirectResponse
    {
        $locale = $request->input('locale');
        $selected = is_string($locale) ? $catalog->active()->firstWhere('code', $locale) : null;
        $request->session()->put('locale', ($selected ?? $catalog->default())->code);
        $page = $request->input('page');
        $allowed = ['dashboard', 'dashboard/examples', 'dashboard/examples/form', 'dashboard/languages', 'dashboard/categories', 'dashboard/categories/create', 'profile'];
        abort_unless(is_string($page) && (in_array($page, $allowed, true) || preg_match('~^dashboard/categories/[1-9][0-9]*/edit$~D', $page)), 404);

        return redirect('/'.$page.($request->getQueryString() ? '?'.$request->getQueryString() : ''));
    }
}
