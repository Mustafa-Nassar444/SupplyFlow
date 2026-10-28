<form method="POST" data-locale="{{ $language?->code }}" action="{{ route('dashboard.locale') }}{{ request()->getQueryString() ? '?'.request()->getQueryString() : '' }}">
    @csrf
    <input type="hidden" name="page" value="{{ request()->path() }}">
    <button class="dropdown-item" name="locale" value="{{ $language?->code }}" lang="{{ $language?->code }}" @if($language?->code === app()->getLocale()) aria-current="true" @endif>{{ $language?->name }}</button>
</form>