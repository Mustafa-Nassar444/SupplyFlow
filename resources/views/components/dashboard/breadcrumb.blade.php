@props(['items' => []])
<nav aria-label="{{ __('ui.breadcrumb') }}"><ol class="breadcrumb mb-3">@foreach($items as $item)<li class="breadcrumb-item {{ empty($item['url']) ? 'active' : '' }}">@if(!empty($item['url']))<a href="{{ $item['url'] }}">{{ $item['label'] }}</a>@else<span aria-current="page">{{ $item['label'] }}</span>@endif</li>@endforeach</ol></nav>
