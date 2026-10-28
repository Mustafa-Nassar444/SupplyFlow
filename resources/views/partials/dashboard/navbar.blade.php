<header class="navbar d-print-none">
    <div class="container-xl justify-content-between">
        <span class="text-secondary">{{ __('ui.preview') }}</span>
        <div class="d-flex align-items-center gap-2">
        @auth
            <div class="dropdown">
                <button class="btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">{{ auth()->user()->name }}</button>
                <div class="dropdown-menu dropdown-menu-end">
                    <a class="dropdown-item" href="{{ route('profile.edit') }}">{{ __('dashboard_ui.profile') }}</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item">{{ __('dashboard_ui.logout') }}</button>
                    </form>
                </div>
            </div>
        @endauth
        <div class="dropdown">
            <button class="btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">{{ __('ui.language') }}</button>
            <div class="dropdown-menu dropdown-menu-end" data-language-switcher>
                @foreach($uiLanguages as $language)
                    @include('partials.dashboard.language-option', ['language' => $language])
                @endforeach
            </div>
        </div>
        </div>
    </div>
</header>
<template data-language-option>
    @include('partials.dashboard.language-option', ['language' => null])
</template>
