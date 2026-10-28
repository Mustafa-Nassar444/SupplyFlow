@extends('layouts.dashboard')
@section('title', __('languages.title'))
@section('content')
    <x-dashboard.page-header :title="__('languages.title')" :description="__('languages.description')" />
    <section id="languages-panel" data-state-url="{{ route('languages.index') }}">
        <div id="languages-message" role="status" aria-live="polite"></div>
        <div class="card">
            <div class="table-responsive">
                <table class="table table-vcenter mb-0">
                    <thead><tr>
                        @foreach(['name', 'code', 'direction', 'active', 'default'] as $column)
                            <th scope="col">{{ __('languages.'.$column) }}</th>
                        @endforeach
                    </tr></thead>
                    <tbody>
                        @foreach($languages as $language)
                            <tr data-language="{{ $language->id }}">
                                <th scope="row">{{ $language->name }}</th>
                                <td><bdi dir="ltr">{{ $language->code }}</bdi></td>
                                <td><bdi dir="ltr">{{ __('languages.'.$language->direction) }}</bdi></td>
                                @foreach(['active' => $language->active, 'default' => $language->id === $defaultId] as $field => $value)
                                    <td>
                                        <label class="form-check form-switch mb-0">
                                            <input type="checkbox" role="switch" class="form-check-input language-toggle"
                                                id="{{ $field }}-{{ $language->id }}" data-url="{{ route('languages.update', $language) }}"
                                                data-field="{{ $field }}" @checked($value)
                                                aria-label="{{ __('languages.'.$field).' — '.$language->name }}">
                                            <span class="form-check-label" data-state-label="{{ $field }}">{{ __('languages.'.($field === 'active' ? ($value ? 'enabled' : 'disabled') : ($value ? 'yes' : 'no'))) }}</span>
                                        </label>
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endsection
@push('scripts')
    <script>window.SupplyFlowLanguages = {{ Illuminate\Support\Js::from(['module' => __('languages'), 'ajax' => __('ajax')]) }};</script>
    <script src="{{ asset('js/languages.js') }}" defer></script>
@endpush
