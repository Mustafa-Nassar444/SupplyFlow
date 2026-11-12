@php($editing = isset($currency))
<form method="POST"
    action="{{ $editing ? route('dashboard.currency-exchanges.update', $currencyExchange) : route('dashboard.currency-exchanges.store') }}"
    class="card js-ajax-form" id="{{ $editing ? 'currency-exchanges-edit-form' : 'currency-exchanges-create-form' }}"
    data-success-action="{{ $editing ? 'redirect' : 'none' }}">
    @csrf
    @if ($editing)
        @method('PUT')
    @endif
    <div class="card-body">

        <div data-ajax-messages></div>
        <div class="row mt-2">

            <div class="col-lg-4 col-md-3 mb-3">
                <label class="form-label" for="currency_id">{{ __('ui.currency_exchanges.currency') }}</label>
                <select id="currency_id" name="currency_id" class="form-control @error('currency_id') is-invalid @enderror"
                    >
                    <option value="">{{ __('ui.select') }}</option>
                    @foreach ($currencies as $currency)
                        <option value="{{ $currency->id }}"
                            {{ old('currency_id', $editing ? $currencyExchange->currency_id : '') == $currency->id ? 'selected' : '' }}>
                            {{ $currency->name }}
                        </option>
                    @endforeach

                </select>
                @error('currency_id')
                    <span class="invalid-feedback d-block">{{ $message }}</span>
                @enderror

            </div>
            <div class="col-lg-4 col-md-3 mb-3">
                <label class="form-label" for="exc_prc">{{ __('ui.currency_exchanges.rate') }}</label>
                <input id="exc_prc" name="exc_prc" type="text"
                    class="form-control @error('exc_prc') is-invalid @enderror"
                    value="{{ old('exc_prc', $editing ? $currencyExchange->exc_prc : '') }}" maxlength="20">
                @error('exc_prc')
                    <span class="invalid-feedback d-block">{{ $message }}</span>
                @enderror

            </div>



        </div>


    </div>
    <div class="card-footer d-flex gap-2">
        <button type="submit" class="btn btn-primary">{{ __('ui.currency_exchanges.save') }}</button>
        <a class="btn" href="{{ route('dashboard.currency-exchanges.index') }}">{{ __('dashboard_ui.cancel') }}</a>
    </div>
</form>
