@extends('layouts.dashboard')
@section('title', __('ui.currency_exchanges.edit'))
@section('content')
    <x-dashboard.breadcrumb :items="[
        ['label' => __('ui.dashboard'), 'url' => route('dashboard')],
        ['label' => __('ui.currency_exchanges.title'), 'url' => route('dashboard.currency-exchanges.index')],
        ['label' => __('ui.currency_exchanges.edit')],
    ]" />
    <x-dashboard.page-header :title="__('ui.currency_exchanges.edit')" :description="__('ui.currency_exchanges.description')" />
    @if($errors->any())
        <x-dashboard.alert type="danger">{{ __('ui.errors') }}</x-dashboard.alert>
    @endif
    @include('dashboard.currencyExchanges._form', ['currencyExchange' => $currencyExchange])
@endsection
@push('scripts')
    <script src="{{ asset('js/translation-tabs.js') }}" defer></script>
@endpush
