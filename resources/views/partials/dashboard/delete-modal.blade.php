<div class="modal fade" id="dashboard-delete-modal" tabindex="-1" aria-labelledby="dashboard-delete-title" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="dashboard-delete-form" method="POST" class="js-ajax-form" data-success-action="reload">
                @csrf
                @method('DELETE')
                <div class="modal-header">
                    <h2 class="modal-title" id="dashboard-delete-title">{{ __('dashboard_ui.confirm_delete') }}</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.close') }}"></button>
                </div>
                <div class="modal-body">
                    <p>{{ __('dashboard_ui.delete_prompt') }} <strong data-delete-record></strong></p>
                    <div data-ajax-messages></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" data-bs-dismiss="modal">{{ __('dashboard_ui.cancel') }}</button>
                    <button type="submit" class="btn btn-danger" disabled>{{ __('dashboard_ui.delete') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
