<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ $uiDirection }}">
<head>
    @include('partials.dashboard.head')
</head>
<body>
<div class="page">
    @include('partials.dashboard.sidebar')
    @include('partials.dashboard.navbar')
    <div class="page-wrapper">
        <main id="main-content" class="page-body">
            <div class="container-xl">
                <div data-ajax-flash></div>
                @yield('content')
            </div>
        </main>
        @include('partials.dashboard.footer')
    </div>
</div>
@include('partials.dashboard.delete-modal')
@include('partials.dashboard.scripts')
</body>
</html>
