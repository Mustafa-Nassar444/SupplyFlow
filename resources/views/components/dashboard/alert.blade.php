@props(['type' => 'info', 'title' => null])
@php($variant = in_array($type, ['info', 'success', 'warning', 'danger'], true) ? $type : 'info')
<div {{ $attributes->class(['alert', 'alert-'.$variant]) }} role="{{ $variant === 'danger' ? 'alert' : 'status' }}">@if($title)<h2 class="alert-title">{{ $title }}</h2>@endif{{ $slot }}</div>
