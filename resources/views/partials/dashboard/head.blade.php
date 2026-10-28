<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', __('ui.dashboard')) — {{ config('app.name') }}</title>
<link rel="stylesheet" href="{{ asset('vendor/tabler/css/tabler'.($uiDirection === 'rtl' ? '.rtl' : '').'.min.css') }}">
<link rel="stylesheet" href="{{ asset('vendor/tabler/fonts/'.config('languages.supported.'.app()->getLocale().'.font').'/400.css') }}">
<link rel="stylesheet" href="{{ asset('vendor/tabler/fonts/'.config('languages.supported.'.app()->getLocale().'.font').'/600.css') }}">
<link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
@stack('styles')
