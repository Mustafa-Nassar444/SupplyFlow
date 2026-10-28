@php($editing = isset($category))
<form method="POST" action="{{ $editing ? route('dashboard.categories.update', $category) : route('dashboard.categories.store') }}"
      class="card js-ajax-form" id="{{ $editing ? 'category-edit-form' : 'category-create-form' }}" data-success-action="{{ $editing ? 'redirect' : 'none' }}">
    @csrf
    @if($editing)
        @method('PUT')
    @endif
    <div class="card-body">
        <div data-ajax-messages></div>
        @php($selectedLanguage = $languages->first(fn ($language) => $errors->has('name_'.$language->code))?->code ?? $languages->first()?->code)
        <div class="nav nav-tabs flex-nowrap overflow-auto mb-3" role="tablist" data-translation-tabs>
            @foreach($languages as $language)
                <button class="nav-link text-nowrap {{ $language->code === $selectedLanguage ? 'active' : '' }}"
                        id="category-language-tab-{{ $language->code }}" type="button" role="tab"
                        data-bs-toggle="tab" data-bs-target="#category-language-pane-{{ $language->code }}"
                        aria-controls="category-language-pane-{{ $language->code }}"
                        aria-selected="{{ $language->code === $selectedLanguage ? 'true' : 'false' }}">
                    {{ $language->name }}
                </button>
            @endforeach
        </div>
        <div class="tab-content">
            @foreach($languages as $language)
                @php($field = 'name_'.$language->code)
                @php($value = old($field, isset($category) ? $category->translate($language->code)?->name : null))
                <div class="tab-pane {{ $language->code === $selectedLanguage ? 'active show' : '' }}"
                     id="category-language-pane-{{ $language->code }}" role="tabpanel"
                     aria-labelledby="category-language-tab-{{ $language->code }}">
                    <label class="form-label required" for="{{ $field }}">{{ __('ui.name') }} — {{ $language->name }}</label>
                    <input id="{{ $field }}" name="{{ $field }}" type="text"
                           class="form-control @error($field) is-invalid @enderror"
                           value="{{ is_string($value) ? $value : '' }}" lang="{{ $language->code }}"
                           dir="{{ $language->direction }}" maxlength="255" required
                           data-ajax-focus="category-language-tab-{{ $language->code }}"
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
        <button type="submit" class="btn btn-primary">{{ __('categories.save') }}</button>
        <a class="btn" href="{{ route('dashboard.categories.index') }}">{{ __('dashboard_ui.cancel') }}</a>
    </div>
</form>
