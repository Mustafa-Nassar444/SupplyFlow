@extends('layouts.dashboard')
@section('title', __('categories.title'))
@section('content')
    <x-dashboard.breadcrumb :items="[['label' => __('ui.dashboard'), 'url' => route('dashboard')], ['label' => __('categories.title')]]" />
    <x-dashboard.page-header :title="__('categories.title')">
        @can('create', App\Models\Category::class)
            <a class="btn btn-primary" href="{{ route('dashboard.categories.create') }}">{{ __('categories.create') }}</a>
        @endcan
    </x-dashboard.page-header>
    @if(session('success'))
        <x-dashboard.alert type="success">{{ session('success') }}</x-dashboard.alert>
    @endif
    <div class="card">
        @if($data->isEmpty())
            <x-dashboard.empty-state :title="__('categories.empty')" />
        @else
            <div class="table-responsive">
                <table class="table table-vcenter demo-table mb-0">
                    <thead><tr><th scope="col">{{ __('ui.name') }}</th><th scope="col">{{ __('ui.actions') }}</th></tr></thead>
                    <tbody>
                        @foreach($data as $item)
                            <tr data-category-id="{{ $item->id }}">
                                <td data-label="{{ __('ui.name') }}">{{ $item->name ?? '—' }}</td>
                                <td data-label="{{ __('ui.actions') }}">
                                    <div class="d-flex gap-2">
                                        @can('update', $item)
                                            <a class="btn btn-sm" href="{{ route('dashboard.categories.edit', $item) }}">{{ __('categories.edit') }}</a>
                                        @endcan
                                        @can('delete', $item)
                                            <button type="button" class="btn btn-sm btn-outline-danger"
                                                    data-delete-url="{{ route('dashboard.categories.destroy', $item) }}"
                                                    data-delete-success-action="redirect"
                                                    data-delete-name="{{ $item->name ?? __('categories.title') }}">
                                                {{ __('dashboard_ui.delete') }}
                                            </button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($data->hasPages())
                <div class="card-footer">{{ $data->links('pagination::bootstrap-5') }}</div>
            @endif
        @endif
    </div>
@endsection
