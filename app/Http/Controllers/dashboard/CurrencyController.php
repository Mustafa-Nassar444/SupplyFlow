<?php

namespace App\Http\Controllers\dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\dashboard\CurrencyRequest;
use App\Http\Traits\TranslationTrait;
use App\Models\Currency;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CurrencyController extends Controller
{
    use TranslationTrait;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        Gate::authorize('viewAny', Currency::class);
        //
        $data = Currency::with('translations')->paginate(10);

        return view('dashboard.currencies.index', compact('data'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Gate::authorize('create', Currency::class);

        return view('dashboard.currencies.create', ['languages' => $this->activeLanguages()]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CurrencyRequest $request): JsonResponse|RedirectResponse
    {
        $currency = DB::transaction(function () use ($request) {
            $data = $request->validated();
             if(isset($data['is_main']) && $data['is_main']  ){
                Currency::where('is_main', true)->update(['is_main' => false]);
            }
            $currency =  Currency::create([
                'symbol' => $data['symbol'] ?? null,
                'is_active' => isset($data['is_active']) && $data['is_active'] && $data['is_active']  ? true: false,
                'is_main' => isset($data['is_main']) && $data['is_main']   ? true: false,
            ]);
            $this->storeTranslates($currency, new Request($data), ['name']);
            $currency->save();


            return $currency;
        }, 3);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => __('ui.currencies.created'),
                'redirect_url' => route('dashboard.currencies.create'),
                'currency_id' => $currency->id,
            ], 201);
        }

        return to_route('dashboard.currencies.create')->with('success', __('ui.currencies.created'));

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
    public function edit(Currency $currency)
    {
        Gate::authorize('update', $currency);

        return view('dashboard.currencies.edit', [
            'currency' => $currency->load('translations'),
            'languages' => $this->activeLanguages(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CurrencyRequest $request, Currency $currency): JsonResponse|RedirectResponse
    {
        DB::transaction(function () use ($request, $currency) {
            $locked = Currency::with('translations')->lockForUpdate()->findOrFail($currency->id);
            $data = $request->validated();
             if(isset($data['is_main']) && $data['is_main']  ){
                Currency::where('is_main', true)->update(['is_main' => false]);
            }
            $locked->update([
                'symbol' => $data['symbol'] ?? null,
                'is_active' => isset($data['is_active']) && $data['is_active']  ? true: false,
                'is_main' => isset($data['is_main']) && $data['is_main']   ? true: false,
            ]);
            $this->storeTranslates($locked, new Request($data), ['name']);
            // Persist translations assigned after the parent update.
            $locked->save();

        }, 3);

        if ($request->expectsJson()) {
            return response()->json(['message' => __('ui.currencies.updated'), 'redirect_url' => route('dashboard.currencies.index')]);
        }

        return to_route('dashboard.currencies.index')->with('success', __('ui.currencies.updated'));
    }

    public function status(Request $request, Currency $currency): JsonResponse
    {
        Gate::authorize('update', $currency);
        $data = $request->validate(['field' => ['required', 'in:is_active,is_main'], 'value' => ['required', 'boolean']]);

        $updated = DB::transaction(function () use ($currency, $data) {
            $locked = Currency::lockForUpdate()->findOrFail($currency->id);
            $value = (bool) $data['value'];
            if ($data['field'] === 'is_main' && $value && ! $locked->is_active) {
                throw ValidationException::withMessages(['status' => __('ui.currencies.activate_first')]);
            }
            if ($data['field'] === 'is_active' && ! $value && $locked->is_main) {
                throw ValidationException::withMessages(['status' => __('ui.currencies.keep_main')]);
            }
            if ($data['field'] === 'is_main' && $value) {
                Currency::where('is_main', true)->where('id', '<>', $locked->id)->update(['is_main' => false]);
            }
            $locked->update([$data['field'] => $value]);

            return $locked->fresh();
        }, 3);

        return response()->json(['message' => __('ui.currencies.updated'), 'currency' => $updated]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Currency $currency): JsonResponse|RedirectResponse
    {
        Gate::authorize('delete', $currency);
        try {
            DB::transaction(function () use ($currency) {
                Currency::lockForUpdate()->findOrFail($currency->id)->delete();
            }, 3);
        } catch (QueryException $exception) {
            if (in_array((int) ($exception->errorInfo[1] ?? 0), [1451, 19], true)) {
                throw ValidationException::withMessages(['delete' => __('ui.currencies.in_use')]);
            }
            throw $exception;
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => __('ui.currencies.deleted'), 'redirect_url' => route('dashboard.currencies.index')]);
        }

        return to_route('dashboard.currencies.index')->with('success', __('ui.currencies.deleted'));
    }
}
