@extends('layouts.dashboard')
@section('title', __('ui.categories.create'))
@section('content')
    <x-dashboard.breadcrumb :items="[
        ['label' => __('ui.dashboard'), 'url' => route('dashboard')],
        ['label' => __('ui.categories.title'), 'url' => route('dashboard.categories.index')],
        ['label' => __('ui.categories.create')],
    ]" />
    <x-dashboard.page-header :title="__('ui.categories.create')" :description="__('ui.categories.description')" />
    @if(session('success'))
        <x-dashboard.alert type="success">{{ session('success') }}</x-dashboard.alert>
    @endif
    @if($errors->any())
        <x-dashboard.alert type="danger">{{ __('ui.errors') }}</x-dashboard.alert>
    @endif
    @include('dashboard.categories._form')
@endsection
@push('scripts')
    <script src="{{ asset('js/translation-tabs.js') }}" defer></script>
@endpush
