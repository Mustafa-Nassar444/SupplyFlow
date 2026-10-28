@extends('layouts.dashboard')
@section('title', __('categories.edit'))
@section('content')
    <x-dashboard.breadcrumb :items="[
        ['label' => __('ui.dashboard'), 'url' => route('dashboard')],
        ['label' => __('categories.title'), 'url' => route('dashboard.categories.index')],
        ['label' => __('categories.edit')],
    ]" />
    <x-dashboard.page-header :title="__('categories.edit')" :description="__('categories.description')" />
    @if($errors->any())
        <x-dashboard.alert type="danger">{{ __('ui.errors') }}</x-dashboard.alert>
    @endif
    @include('dashboard.categories._form', ['category' => $category])
@endsection
@push('scripts')
    <script src="{{ asset('js/translation-tabs.js') }}" defer></script>
@endpush
