<section class="card h-100">
    <div class="card-body">
        <h2 class="card-title">{{ __('profile_ui.password_title') }}</h2>
        <p class="text-secondary">{{ __('profile_ui.password_description') }}</p>
        @if(session('status') === 'password-updated')
            <x-dashboard.alert type="success">{{ __('profile_ui.saved') }}</x-dashboard.alert>
        @endif
        <form id="profile-password-form" method="POST" action="{{ route('password.update') }}">
            @csrf
            @method('PUT')
            @foreach(['current_password' => 'current_password', 'password' => 'new_password', 'password_confirmation' => 'confirm_password'] as $field => $label)
                <div class="mb-3">
                    <label class="form-label required" for="profile-{{ $field }}">{{ __('profile_ui.'.$label) }}</label>
                    <input id="profile-{{ $field }}" name="{{ $field }}" type="password"
                           class="form-control @error($field, 'updatePassword') is-invalid @enderror" required
                           autocomplete="{{ $field === 'current_password' ? 'current-password' : 'new-password' }}"
                           aria-describedby="profile-{{ $field }}-error" @error($field, 'updatePassword') aria-invalid="true" @enderror>
                    <div id="profile-{{ $field }}-error">
                        @error($field, 'updatePassword')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    </div>
                </div>
            @endforeach
            <button type="submit" class="btn btn-primary">{{ __('profile_ui.save') }}</button>
        </form>
    </div>
</section>
