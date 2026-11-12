@extends('layouts.dashboard')
@section('title', __('ui.currencies.create'))
@section('content')
    <x-dashboard.breadcrumb :items="[
        ['label' => __('ui.dashboard'), 'url' => route('dashboard')],
        ['label' => __('ui.currencies.title'), 'url' => route('dashboard.currencies.index')],
        ['label' => __('ui.currencies.create')],
    ]" />
    <x-dashboard.page-header :title="__('ui.currencies.create')" :description="__('ui.currencies.description')" />
    @if(session('success'))
        <x-dashboard.alert type="success">{{ session('success') }}</x-dashboard.alert>
    @endif
    @if($errors->any())
        <x-dashboard.alert type="danger">{{ __('ui.errors') }}</x-dashboard.alert>
    @endif
    @include('dashboard.currencies._form')
@endsection
@push('scripts')
    <script src="{{ asset('js/translation-tabs.js') }}" defer></script>
@endpush
