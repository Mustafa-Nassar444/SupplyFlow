@props(['title', 'description' => null])
<div {{ $attributes->class(['page-header', 'mb-4']) }}><div class="row align-items-center"><div class="col"><h1 class="page-title">{{ $title }}</h1>@if($description)<p class="text-secondary mt-2">{{ $description }}</p>@endif</div>@if($slot->isNotEmpty())<div class="col-auto">{{ $slot }}</div>@endif</div></div>
