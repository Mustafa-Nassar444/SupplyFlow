@extends('layouts.dashboard')
@section('title', __('categories.create'))
@section('content')
    <x-dashboard.breadcrumb :items="[
        ['label' => __('ui.dashboard'), 'url' => route('dashboard')],
        ['label' => __('categories.title'), 'url' => route('dashboard.categories.index')],
        ['label' => __('categories.create')],
    ]" />
    <x-dashboard.page-header :title="__('categories.create')" :description="__('categories.description')" />
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
