<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateLanguageRequest;
use App\Models\Language;
use App\Services\Languages\LanguageCatalog;
use App\Services\Languages\UpdateLanguage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LanguageController extends Controller
{
    public function index(Request $request, LanguageCatalog $catalog): View|JsonResponse
    {
        abort_unless($catalog->defaultId(), 503, __('languages.uninitialized'));
        if ($request->expectsJson()) {
            return response()->json(['languages' => $catalog->snapshot(), 'active_languages' => $catalog->options()])->header('Cache-Control', 'no-store');
        }

        return view('languages.index', ['languages' => $catalog->all(), 'defaultId' => $catalog->defaultId()]);
    }

    public function update(UpdateLanguageRequest $request, Language $language, UpdateLanguage $action): JsonResponse
    {
        $rows = $action->execute($language->id, $request->validated('field'), (bool) $request->validated('value'));

        return response()->json(['message' => __('languages.saved'), 'languages' => $rows, 'active_languages' => app(LanguageCatalog::class)->options()]);
    }
}
