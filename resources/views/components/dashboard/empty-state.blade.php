@props(['title', 'description' => null])
<div {{ $attributes->class(['empty']) }}><h2 class="empty-title">{{ $title }}</h2>@if($description)<p class="empty-subtitle text-secondary">{{ $description }}</p>@endif
@if($slot->isNotEmpty())<div class="empty-action">{{ $slot }}</div>@endif</div>
