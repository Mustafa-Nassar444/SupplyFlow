@extends('layouts.dashboard')
@section('title', __('ui.currency_exchanges.title'))
@section('content')
    <x-dashboard.breadcrumb :items="[['label' => __('ui.dashboard'), 'url' => route('dashboard')], ['label' => __('ui.currency_exchanges.title')]]" />
    <x-dashboard.page-header :title="__('ui.currency_exchanges.title')">
        @can('create', App\Models\CurrencyExchange::class)
            <a class="btn btn-primary" href="{{ route('dashboard.currency-exchanges.create') }}">{{ __('ui.currency_exchanges.create') }}</a>
        @endcan
    </x-dashboard.page-header>
    <div class="card">
        @if($data->isEmpty())
            <x-dashboard.empty-state :title="__('ui.currency_exchanges.empty')" />
        @else
            <div class="table-responsive"><table class="table table-vcenter mb-0"><thead><tr>
                <th>{{ __('ui.currency_exchanges.base_currency') }}</th><th>{{ __('ui.currency_exchanges.currency') }}</th><th>{{ __('ui.currency_exchanges.rate') }}</th><th>{{ __('ui.actions') }}</th>
            </tr></thead><tbody>
            @foreach($data as $item)<tr>
                <td>{{ $item->baseCurrency?->name ?? '—' }}</td><td>{{ $item->currency?->name ?? '—' }}</td><td>{{ $item->exc_prc }}</td>
                <td><div class="d-flex gap-2">
                    @can('update', $item)<a class="btn btn-sm" href="{{ route('dashboard.currency-exchanges.edit', $item) }}">{{ __('ui.currency_exchanges.edit') }}</a>@endcan
                    @can('delete', $item)<button type="button" class="btn btn-sm btn-outline-danger" data-delete-url="{{ route('dashboard.currency-exchanges.destroy', $item) }}" data-delete-success-action="redirect" data-delete-name="{{ __('ui.currency_exchanges.title') }}">{{ __('dashboard_ui.delete') }}</button>@endcan
                </div></td>
            </tr>@endforeach
            </tbody></table></div>
            @if($data->hasPages())<div class="card-footer">{{ $data->links('pagination::bootstrap-5') }}</div>@endif
        @endif
    </div>
@endsection
