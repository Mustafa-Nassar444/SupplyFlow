<script src="{{ asset('vendor/tabler/js/tabler.js') }}" defer></script>
<script>window.SupplyFlowAjaxMessages = {{ Illuminate\Support\Js::from(__('ajax')) }};</script>
<script src="{{ asset('js/ajax-forms.js') }}" defer></script>
<script src="{{ asset('js/dashboard-delete.js') }}" defer></script>
@stack('scripts')
