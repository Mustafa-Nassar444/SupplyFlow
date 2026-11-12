@extends('layouts.dashboard')
@section('title', __('ui.currencies.title'))
@section('content')
    <x-dashboard.breadcrumb :items="[['label' => __('ui.dashboard'), 'url' => route('dashboard')], ['label' => __('ui.currencies.title')]]" />
    <x-dashboard.page-header :title="__('ui.currencies.title')">
        @can('create', App\Models\Currency::class)
            <a class="btn btn-primary" href="{{ route('dashboard.currencies.create') }}">{{ __('ui.currencies.create') }}</a>
        @endcan
    </x-dashboard.page-header>
    @if(session('success'))
        <x-dashboard.alert type="success">{{ session('success') }}</x-dashboard.alert>
    @endif
    <div class="card">
        @if($data->isEmpty())
            <x-dashboard.empty-state :title="__('ui.currencies.empty')" />
        @else
            <div class="table-responsive">
                <table class="table table-vcenter demo-table mb-0">
                    <thead>
                        <tr>
                            <th scope="col">{{ __('ui.name') }}</th>
                            <th scope="col">{{ __('ui.main') }}</th>
                            <th scope="col">{{ __('ui.active') }}</th>
                            <th scope="col">{{ __('ui.actions') }}</th>
                        </tr></thead>
                    <tbody>
                        @foreach($data as $item)
                            <tr data-category-id="{{ $item->id }}">
                                <td data-label="{{ __('ui.name') }}">{{ $item->name ?? '---' }}</td>
                                <td data-label="{{ __('ui.currencies.is_main') }}">
                                    <label class="form-check form-switch mb-0"><input class="form-check-input currency-toggle" type="checkbox" role="switch" data-field="is_main" data-url="{{ route('dashboard.currencies.status', $item) }}" @checked($item->is_main) aria-label="{{ __('ui.currencies.is_main').' — '.$item->name }}"><span class="visually-hidden">{{ __('ui.currencies.is_main') }}</span></label>
                                </td>
                                <td data-label="{{ __('ui.currencies.is_active') }}">
                                    <label class="form-check form-switch mb-0"><input class="form-check-input currency-toggle" type="checkbox" role="switch" data-field="is_active" data-url="{{ route('dashboard.currencies.status', $item) }}" @checked($item->is_active) aria-label="{{ __('ui.currencies.is_active').' — '.$item->name }}"><span class="visually-hidden">{{ __('ui.currencies.is_active') }}</span></label>
                                </td>
                                <td data-label="{{ __('ui.actions') }}">
                                    <div class="d-flex gap-2">
                                        @can('update', $item)
                                            <a class="btn btn-sm" href="{{ route('dashboard.currencies.edit', $item) }}">{{ __('ui.currencies.edit') }}</a>
                                        @endcan
                                        @can('delete', $item)
                                            <button type="button" class="btn btn-sm btn-outline-danger"
                                                    data-delete-url="{{ route('dashboard.currencies.destroy', $item) }}"
                                                    data-delete-success-action="redirect"
                                                    data-delete-name="{{ $item->name ?? __('ui.currencies.title') }}">
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
@push('scripts')
    <script>window.SupplyFlowCurrencies = {{ Illuminate\Support\Js::from(['messages' => ['success' => __('ui.currencies.updated'), 'error' => __('ui.errors')]]) }};</script>
    <script src="{{ asset('js/currencies.js') }}" defer></script>
@endpush
