@extends('layouts.dashboard')
@section('title', __('ui.form'))
@section('content')
    <x-dashboard.breadcrumb :items="[['label' => __('ui.dashboard'), 'url' => route('dashboard.index')], ['label' => __('ui.form')]]" />
    <x-dashboard.page-header :title="__('ui.form')" :description="__('ui.form_description')" />
    @if(session('success'))<x-dashboard.alert type="success">{{ session('success') }}</x-dashboard.alert>@endif
    @if($errors->any())<x-dashboard.alert type="danger">{{ __('ui.errors') }}</x-dashboard.alert>@endif
    <h2 class="h3">{{ __('ajax.create') }}</h2>
    <form method="POST" action="{{ route('dashboard.examples.store') }}" class="card js-ajax-form mb-4" id="demo-form" data-success-action="none">
        @csrf
        <div class="card-body">
            @foreach(['name' => 'text', 'email' => 'email'] as $field => $type)
                <div class="mb-3"><label class="form-label" for="{{ $field }}">{{ __('ui.'.$field) }}</label><input id="{{ $field }}" name="{{ $field }}" type="{{ $type }}" value="{{ old($field) }}" maxlength="{{ $field === 'name' ? 100 : 255 }}" class="form-control @error($field) is-invalid @enderror" @if($field === 'email') dir="ltr" @endif aria-describedby="{{ $field }}-error" @error($field) aria-invalid="true" @enderror><div id="{{ $field }}-error">@error($field)<span class="invalid-feedback d-block">{{ $message }}</span>@enderror</div></div>
            @endforeach
            <div class="mb-3"><label class="form-label" for="category">{{ __('ui.category') }}</label><select class="form-select @error('category') is-invalid @enderror" id="category" name="category" aria-describedby="category-error">@foreach(['sample', 'other'] as $value)<option value="{{ $value }}" @selected(old('category') === $value)>{{ __('ui.'.$value) }}</option>@endforeach</select><div id="category-error">@error('category')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror</div></div>
            <div class="mb-3"><label class="form-label" for="notes">{{ __('ui.notes') }}</label><textarea id="notes" name="notes" maxlength="1000" class="form-control @error('notes') is-invalid @enderror" aria-describedby="notes-error">{{ old('notes') }}</textarea><div id="notes-error">@error('notes')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror</div></div>
            <label class="form-check"><input id="acknowledge" type="checkbox" class="form-check-input @error('acknowledge') is-invalid @enderror" name="acknowledge" value="1" @checked(old('acknowledge')) aria-describedby="acknowledge-error"><span class="form-check-label">{{ __('ui.acknowledge') }}</span></label><div id="acknowledge-error">@error('acknowledge')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror</div>
        </div>
        <div class="card-footer"><button type="submit" class="btn btn-primary" data-loading-label="{{ __('ui.loading') }}">{{ __('ui.submit') }}</button></div>
    </form>
    <h2 class="h3">{{ __('ajax.edit') }}</h2>
    <form method="POST" action="{{ route('dashboard.examples.update') }}" class="card js-ajax-form" enctype="multipart/form-data" data-success-action="reset">
        @csrf
        @method('PATCH')
        <div class="card-body">
            <div data-ajax-messages></div>
            <div class="mb-3">
                <label for="edit-name" class="form-label">{{ __('ui.name') }}</label>
                <input id="edit-name" name="name" class="form-control" value="{{ __('ui.sample_name') }}" maxlength="100" required>
            </div>
            <div class="mb-3">
                <label for="edit-quantity" class="form-label">{{ __('ajax.quantity') }}</label>
                <input id="edit-quantity" name="items[0][quantity]" type="number" class="form-control" value="0" required>
                <div data-ajax-error-for="items.0.quantity"></div>
            </div>
            <div class="mb-3">
                <label for="edit-file" class="form-label">{{ __('ajax.attachment') }}</label>
                <input id="edit-file" name="attachment" type="file" class="form-control">
                <div data-ajax-error-for="attachment"></div>
            </div>
        </div>
        <div class="card-footer"><button type="submit" name="intent" value="validate" class="btn btn-primary">{{ __('ajax.submit') }}</button></div>
    </form>
@endsection
