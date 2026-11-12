<?php

namespace App\Http\Controllers\dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\dashboard\CurrencyExchangeRequest;
use App\Models\Currency;
use App\Models\CurrencyExchange;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CurrencyExchangeController extends Controller
{

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        Gate::authorize('viewAny', CurrencyExchange::class);
        //
        $data = CurrencyExchange::with(['baseCurrency', 'currency.translations'])->latest()->paginate(10);

        return view('dashboard.currencyExchanges.index', compact('data'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Gate::authorize('create', CurrencyExchange::class);
        $currencies = Currency::where('is_active', true)->where('is_main', false)->with('translations')->get();
        return view('dashboard.currencyExchanges.create', ['currencies' => $currencies]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CurrencyExchangeRequest $request): JsonResponse|RedirectResponse
    {
        $mainCurrency = Currency::where('is_main', true)->where('is_active', true)->first();
        if (! $mainCurrency) throw ValidationException::withMessages(['currency_id' => __('ui.currency_exchanges.main_currency_required')]);
        $currencyExchange = DB::transaction(function () use ($request ,$mainCurrency) {
            $data = $request->validated();
            $currencyExchange = CurrencyExchange::create([
                'base_currency_id' => $mainCurrency->id,
                'currency_id' => $data['currency_id'],
                'exc_prc' => $data['exc_prc'],
            ]);


            return $currencyExchange;
        }, 3);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => __('ui.currency_exchanges.created'),
                'redirect_url' => route('dashboard.currency-exchanges.create'),
                'currency_exchange_id' => $currencyExchange->id,
            ], 201);
        }

        return to_route('dashboard.currency-exchanges.create')->with('success', __('ui.currency_exchanges.created'));

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
    public function edit(CurrencyExchange $currencyExchange)
    {
        Gate::authorize('update', $currencyExchange);
        $currencies = Currency::where('is_active', true)->where('is_main', false)->with('translations')->get();

        return view('dashboard.currencyExchanges.edit', [
            'currencyExchange' => $currencyExchange->load('currency.translations'), 'currencies' => $currencies,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CurrencyExchangeRequest $request, CurrencyExchange $currencyExchange): JsonResponse|RedirectResponse
    {
        $currencyExchange->update($request->validated());

        if ($request->expectsJson()) {
            return response()->json(['message' => __('ui.currency_exchanges.updated'), 'redirect_url' => route('dashboard.currency-exchanges.index')]);
        }

        return to_route('dashboard.currency-exchanges.index')->with('success', __('ui.currency_exchanges.updated'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, CurrencyExchange $currencyExchange): JsonResponse|RedirectResponse
    {
        Gate::authorize('delete', $currencyExchange);
        try {
            DB::transaction(function () use ($currencyExchange) {
                CurrencyExchange::lockForUpdate()->findOrFail($currencyExchange->id)->delete();
            }, 3);
        } catch (QueryException $exception) {
            if (in_array((int) ($exception->errorInfo[1] ?? 0), [1451, 19], true)) {
                throw ValidationException::withMessages(['delete' => __('ui.currency_exchanges.in_use')]);
            }
            throw $exception;
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => __('ui.currency_exchanges.deleted'), 'redirect_url' => route('dashboard.currency-exchanges.index')]);
        }

        return to_route('dashboard.currency-exchanges.index')->with('success', __('ui.currency_exchanges.deleted'));
    }
}
