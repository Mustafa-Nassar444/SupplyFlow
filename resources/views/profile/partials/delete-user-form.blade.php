<section class="card">
    <div class="card-body">
        <h2 class="card-title">{{ __('profile_ui.delete_title') }}</h2>
        <p class="text-secondary">{{ __('profile_ui.delete_description') }}</p>
        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#confirm-user-deletion">
            {{ __('profile_ui.delete_title') }}
        </button>
    </div>
</section>

<div class="modal fade" id="confirm-user-deletion" tabindex="-1" aria-labelledby="confirm-user-deletion-title"
     aria-hidden="true" data-open-on-error="{{ $errors->userDeletion->isNotEmpty() ? 'true' : 'false' }}">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('profile.destroy') }}">
                @csrf
                @method('DELETE')
                <div class="modal-header">
                    <h2 class="modal-title" id="confirm-user-deletion-title">{{ __('profile_ui.delete_title') }}</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.close') }}"></button>
                </div>
                <div class="modal-body">
                    <p>{{ __('profile_ui.delete_confirmation') }}</p>
                    <label class="form-label required" for="delete-account-password">{{ __('profile_ui.current_password') }}</label>
                    <input id="delete-account-password" name="password" type="password" required autocomplete="current-password"
                           class="form-control @error('password', 'userDeletion') is-invalid @enderror"
                           aria-describedby="delete-account-password-error" @error('password', 'userDeletion') aria-invalid="true" @enderror>
                    <div id="delete-account-password-error">
                        @error('password', 'userDeletion')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" data-bs-dismiss="modal">{{ __('dashboard_ui.cancel') }}</button>
                    <button type="submit" class="btn btn-danger">{{ __('profile_ui.delete_title') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
