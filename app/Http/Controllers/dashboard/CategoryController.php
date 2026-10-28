<?php

namespace App\Http\Controllers\dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\dashboard\CategoryRequest;
use App\Http\Traits\TranslationTrait;
use App\Models\Category;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CategoryController extends Controller
{
    use TranslationTrait;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        Gate::authorize('viewAny', Category::class);
        //
        $data = Category::with('translations')->paginate(10);

        return view('dashboard.categories.index', compact('data'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Gate::authorize('create', Category::class);

        return view('dashboard.categories.create', ['languages' => $this->activeLanguages()]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CategoryRequest $request): JsonResponse|RedirectResponse
    {
        $category = DB::transaction(function () use ($request) {
            $data = $request->validated();
            $category = new Category;
            $this->storeTranslates($category, new Request($data), ['name']);
            $category->save();

            return $category;
        }, 3);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => __('categories.created'),
                'redirect_url' => route('dashboard.categories.create'),
                'category_id' => $category->id,
            ], 201);
        }

        return to_route('dashboard.categories.create')->with('success', __('categories.created'));

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Category $category)
    {
        Gate::authorize('update', $category);

        return view('dashboard.categories.edit', [
            'category' => $category->load('translations'),
            'languages' => $this->activeLanguages(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CategoryRequest $request, Category $category): JsonResponse|RedirectResponse
    {
        DB::transaction(function () use ($request, $category) {
            $locked = Category::with('translations')->lockForUpdate()->findOrFail($category->id);
            $this->storeTranslates($locked, new Request($request->validated()), ['name']);
            $locked->save();
        }, 3);

        if ($request->expectsJson()) {
            return response()->json(['message' => __('categories.updated'), 'redirect_url' => route('dashboard.categories.index')]);
        }

        return to_route('dashboard.categories.index')->with('success', __('categories.updated'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Category $category): JsonResponse|RedirectResponse
    {
        Gate::authorize('delete', $category);
        try {
            DB::transaction(function () use ($category) {
                Category::lockForUpdate()->findOrFail($category->id)->delete();
            }, 3);
        } catch (QueryException $exception) {
            if (in_array((int) ($exception->errorInfo[1] ?? 0), [1451, 19], true)) {
                throw ValidationException::withMessages(['delete' => __('categories.in_use')]);
            }
            throw $exception;
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => __('categories.deleted'), 'redirect_url' => route('dashboard.categories.index')]);
        }

        return to_route('dashboard.categories.index')->with('success', __('categories.deleted'));
    }
}
