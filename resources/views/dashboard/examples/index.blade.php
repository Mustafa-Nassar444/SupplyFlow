@extends('layouts.dashboard')
@section('title', __('ui.list'))
@section('content')
    <x-dashboard.breadcrumb :items="[['label' => __('ui.dashboard'), 'url' => route('dashboard.index')], ['label' => __('ui.list')]]" />
    <x-dashboard.page-header :title="__('ui.list')" :description="__('ui.list_description')"><a class="btn btn-primary" href="{{ route('dashboard.examples.form') }}">{{ __('ui.view_form') }}</a></x-dashboard.page-header>
    <div class="card">
        <div class="card-body"><div class="row g-3"><div class="col-md-8"><label class="form-label" for="search">{{ __('ui.search') }}</label><input id="search" class="form-control" disabled></div><div class="col-md-4"><label class="form-label" for="filter">{{ __('ui.filter') }}</label><select id="filter" class="form-select" disabled><option>{{ __('ui.all') }}</option></select></div></div></div>
        <div class="table-responsive"><table class="table table-vcenter demo-table mb-0"><thead><tr>@foreach(['name', 'reference', 'email', 'actions'] as $label)<th scope="col">{{ __('ui.'.$label) }}</th>@endforeach</tr></thead><tbody>
            @foreach([1, 2, 3] as $number)
                <tr><td data-label="{{ __('ui.name') }}">{{ __('ui.sample_name') }} {{ $number }}</td><td data-label="{{ __('ui.reference') }}"><bdi dir="ltr">DEMO-00{{ $number }}</bdi></td><td data-label="{{ __('ui.email') }}"><bdi dir="ltr">demo{{ $number }}@example.test</bdi></td><td data-label="{{ __('ui.actions') }}"><button class="btn btn-sm" data-bs-toggle="modal" data-bs-target="#demo-details">{{ __('ui.details') }}</button></td></tr>
            @endforeach
        </tbody></table></div>
    </div>
    <div class="modal fade" id="demo-details" tabindex="-1" aria-labelledby="demo-details-title" aria-hidden="true"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h2 id="demo-details-title" class="modal-title">{{ __('ui.demo_title') }}</h2><button class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.close') }}"></button></div><div class="modal-body">{{ __('ui.notice') }}</div></div></div></div>
@endsection
