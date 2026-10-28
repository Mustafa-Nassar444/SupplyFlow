@extends('layouts.dashboard')
@section('title', __('dashboard_ui.profile'))
@section('content')
    <x-dashboard.breadcrumb :items="[
        ['label' => __('ui.dashboard'), 'url' => route('dashboard')],
        ['label' => __('dashboard_ui.profile')],
    ]" />
    <x-dashboard.page-header :title="__('dashboard_ui.profile')" />
    <div class="row row-cards">
        <div class="col-12 col-xl-6">
            @include('profile.partials.update-profile-information-form')
        </div>
        <div class="col-12 col-xl-6">
            @include('profile.partials.update-password-form')
        </div>
        <div class="col-12">
            @include('profile.partials.delete-user-form')
        </div>
    </div>
@endsection
@push('scripts')
    <script src="{{ asset('js/profile.js') }}" defer></script>
@endpush
