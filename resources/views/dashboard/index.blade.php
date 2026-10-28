@extends('layouts.dashboard')
@section('title', __('ui.dashboard'))
@section('content')
    <x-dashboard.page-header :title="__('ui.dashboard')" :description="__('ui.description')" />
    <x-dashboard.alert>{{ __('ui.notice') }}</x-dashboard.alert>
    <div class="row row-cards mb-4">
        @foreach(['orders' => 12, 'products' => 24, 'customers' => 8] as $label => $value)
            <div class="col-sm-6 col-lg-4"><div class="card"><div class="card-body"><div class="subheader">{{ __('ui.'.$label) }}</div><div class="h1 mb-0">{{ $value }}</div></div></div></div>
        @endforeach
    </div>
    <div class="card"><x-dashboard.empty-state :title="__('ui.empty_title')" :description="__('ui.empty_description')">
        @can('viewAny', App\Models\Category::class)
            <a class="btn btn-primary" href="{{ route('dashboard.categories.index') }}">{{ __('categories.title') }}</a>
        @endcan
    </x-dashboard.empty-state></div>
@endsection
