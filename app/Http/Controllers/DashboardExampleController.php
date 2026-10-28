<?php

namespace App\Http\Controllers;

use App\Http\Requests\AjaxExampleRequest;
use App\Http\Requests\DashboardExampleRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class DashboardExampleController extends Controller
{
    public function store(DashboardExampleRequest $request): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => __('ui.success'), 'redirect_url' => route('dashboard.examples.form')]);
        }

        return to_route('dashboard.examples.form')->with('success', __('ui.success'));
    }

    public function update(AjaxExampleRequest $request): JsonResponse
    {
        // Demonstration only: validate the multipart payload; do not persist files or records.
        return response()->json(['message' => __('ui.success'), 'redirect_url' => route('dashboard.examples.form')]);
    }
}
