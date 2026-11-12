@extends('layouts.dashboard')
@section('title', __('ui.currency_exchanges.create'))
@section('content')
    <x-dashboard.breadcrumb :items="[
        ['label' => __('ui.dashboard'), 'url' => route('dashboard')],
        ['label' => __('ui.currency_exchanges.title'), 'url' => route('dashboard.currency-exchanges.index')],
        ['label' => __('ui.currency_exchanges.create')],
    ]" />
    <x-dashboard.page-header :title="__('ui.currency_exchanges.create')" :description="__('ui.currency_exchanges.description')" />
    @if(session('success'))
        <x-dashboard.alert type="success">{{ session('success') }}</x-dashboard.alert>
    @endif
    @if($errors->any())
        <x-dashboard.alert type="danger">{{ __('ui.errors') }}</x-dashboard.alert>
    @endif
    @include('dashboard.currencyExchanges._form')
@endsection
@push('scripts')
    <script src="{{ asset('js/translation-tabs.js') }}" defer></script>
@endpush
