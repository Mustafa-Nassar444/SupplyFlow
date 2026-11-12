@php($editing = isset($currency))
<form method="POST" action="{{ $editing ? route('dashboard.currencies.update', $currency) : route('dashboard.currencies.store') }}"
      class="card js-ajax-form" id="{{ $editing ? 'currency-edit-form' : 'currency-create-form' }}" data-success-action="{{ $editing ? 'redirect' : 'none' }}">
    @csrf
    @if($editing)
        @method('PUT')
    @endif
    <div class="card-body">

        <div data-ajax-messages></div>
        <div class="row mt-2">

        <div class="col-lg-4 col-md-3 mb-3">
            <label class="form-label" for="symbol">{{ __('ui.currencies.symbol') }}</label>
            <input id="symbol" name="symbol" type="text"
                   class="form-control @error('symbol') is-invalid @enderror"
                   value="{{ old('symbol', $editing ? $currency->symbol : '') }}" maxlength="10">
            @error('symbol')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror

        </div>
        <div class="col-lg-4 col-md-4 mb-3 mt-5">
            <div class="form-check form-switch">
                <input id="is_active" name="is_active" type="checkbox"
                       class="form-check-input @error('is_active') is-invalid @enderror"
                       value="1" {{ old('is_active', $editing ? $currency->is_active : true) ? 'checked' : '' }}>
                <label class="form-check-label" for="is_active">{{ __('ui.currencies.is_active') }}</label>
            </div>
            @error('is_active')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>
        <div class="col-lg-4 col-md-4 mb-3 mt-5">
            <div class="form-check form-switch">
                <input id="is_main" name="is_main" type="checkbox"
                       class="form-check-input @error('is_main') is-invalid @enderror"
                       value="1" {{ old('is_main', $editing ? $currency->is_main : true) ? 'checked' : '' }}>
                <label class="form-check-label" for="is_main">{{ __('ui.currencies.is_main') }}</label>
            </div>
            @error('is_main')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>
        </div>

        @php($selectedLanguage = $languages->first(fn ($language) => $errors->has('name_'.$language->code))?->code ?? $languages->first()?->code)
        <div class="nav nav-tabs flex-nowrap overflow-auto mb-3" role="tablist" data-translation-tabs>
            @foreach($languages as $language)
                <button class="nav-link text-nowrap {{ $language->code === $selectedLanguage ? 'active' : '' }}"
                        id="currency-language-tab-{{ $language->code }}" type="button" role="tab"
                        data-bs-toggle="tab" data-bs-target="#currency-language-pane-{{ $language->code }}"
                        aria-controls="currency-language-pane-{{ $language->code }}"
                        aria-selected="{{ $language->code === $selectedLanguage ? 'true' : 'false' }}">
                    {{ $language->name }}
                </button>
            @endforeach
        </div>
        <div class="tab-content">
            @foreach($languages as $language)
                @php($field = 'name_'.$language->code)
                @php($value = old($field, isset($currency) ? $currency->translate($language->code)?->name : null))
                <div class="tab-pane {{ $language->code === $selectedLanguage ? 'active show' : '' }}"
                     id="currency-language-pane-{{ $language->code }}" role="tabpanel"
                     aria-labelledby="currency-language-tab-{{ $language->code }}">
                    <label class="form-label required" for="{{ $field }}">{{ __('ui.name') }} ( {{ $language->name }} )</label>
                    <input id="{{ $field }}" name="{{ $field }}" type="text"
                           class="form-control @error($field) is-invalid @enderror"
                           value="{{ is_string($value) ? $value : '' }}" lang="{{ $language->code }}"
                           dir="{{ $language->direction }}" maxlength="255" required
                           data-ajax-focus="currency-language-tab-{{ $language->code }}"
                           aria-describedby="{{ $field }}-error" @error($field) aria-invalid="true" @enderror>
                    <div id="{{ $field }}-error" data-ajax-error-for="{{ $field }}">
                        @error($field)
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    <div class="card-footer d-flex gap-2">
        <button type="submit" class="btn btn-primary">{{ __('ui.currencies.save') }}</button>
        <a class="btn" href="{{ route('dashboard.currencies.index') }}">{{ __('dashboard_ui.cancel') }}</a>
    </div>
</form>
