<a class="visually-hidden-focusable" href="#main-content">{{ __('ui.skip') }}</a>
<aside class="navbar navbar-vertical navbar-expand-lg" aria-label="{{ __('ui.navigation') }}">
    <div class="container-fluid">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-menu" aria-controls="sidebar-menu" aria-expanded="false" aria-label="{{ __('ui.navigation') }}"><span class="navbar-toggler-icon"></span></button>
        <a class="navbar-brand" href="{{ route('dashboard') }}">{{ config('app.name') }}</a>
        <div class="collapse navbar-collapse" id="sidebar-menu">
            <ul class="navbar-nav pt-lg-3">
                <li class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('dashboard') }}"><span class="nav-link-icon">@include('partials.dashboard.icon-layout-dashboard')</span><span class="nav-link-title">{{ __('ui.dashboard') }}</span></a>
                </li>
                @can('viewAny', App\Models\Language::class)
                    <li class="nav-item {{ request()->routeIs('languages.*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('languages.index') }}" @if(request()->routeIs('languages.*')) aria-current="page" @endif>{{ __('languages.title') }}</a>
                    </li>
                @endcan
                @can('viewAny', App\Models\Category::class)
                    <li class="nav-item {{ request()->routeIs('dashboard.categories.*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('dashboard.categories.index') }}" @if(request()->routeIs('dashboard.categories.*')) aria-current="page" @endif>{{ __('categories.title') }}</a>
                    </li>
                @endcan
                @if(Route::has('dashboard.examples.index'))
                    <li class="nav-item dropdown {{ request()->routeIs('dashboard.examples.*') ? 'active' : '' }}">
                        <a class="nav-link dropdown-toggle" href="#examples-menu" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->routeIs('dashboard.examples.*') ? 'true' : 'false' }}" aria-controls="examples-menu"><span class="nav-link-title">{{ __('ui.examples') }}</span></a>
                        <div id="examples-menu" class="collapse {{ request()->routeIs('dashboard.examples.*') ? 'show' : '' }}">
                            <ul class="nav nav-pills flex-column">
                                @foreach(['index' => 'list', 'form' => 'form'] as $page => $label)
                                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('dashboard.examples.'.$page) ? 'active' : '' }}" href="{{ route('dashboard.examples.'.$page) }}">{{ __('ui.'.$label) }}</a></li>
                                @endforeach
                            </ul>
                        </div>
                    </li>
                @endif
            </ul>
        </div>
    </div>
</aside>
