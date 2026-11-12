@extends('layouts.dashboard')
@section('title', __('ui.currencies.edit'))
@section('content')
    <x-dashboard.breadcrumb :items="[
        ['label' => __('ui.dashboard'), 'url' => route('dashboard')],
        ['label' => __('ui.currencies.title'), 'url' => route('dashboard.currencies.index')],
        ['label' => __('ui.currencies.edit')],
    ]" />
    <x-dashboard.page-header :title="__('ui.currencies.edit')" :description="__('ui.currencies.description')" />
    @if($errors->any())
        <x-dashboard.alert type="danger">{{ __('ui.errors') }}</x-dashboard.alert>
    @endif
    @include('dashboard.currencies._form', ['currency' => $currency])
@endsection
@push('scripts')
    <script src="{{ asset('js/translation-tabs.js') }}" defer></script>
@endpush
