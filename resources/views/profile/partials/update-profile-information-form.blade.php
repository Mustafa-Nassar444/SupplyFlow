<section class="card h-100">
    <div class="card-body">
        <h2 class="card-title">{{ __('profile_ui.information') }}</h2>
        <p class="text-secondary">{{ __('profile_ui.information_description') }}</p>
        @if(session('status') === 'profile-updated')
            <x-dashboard.alert type="success">{{ __('profile_ui.saved') }}</x-dashboard.alert>
        @endif
        <form id="send-verification" method="POST" action="{{ route('verification.send') }}">
            @csrf
        </form>
        <form id="profile-information-form" method="POST" action="{{ route('profile.update') }}">
            @csrf
            @method('PATCH')
            @php($name = old('name', $user->name))
            @php($email = old('email', $user->email))
            <div class="mb-3">
                <label class="form-label required" for="profile-name">{{ __('ui.name') }}</label>
                <input id="profile-name" name="name" type="text" class="form-control @error('name') is-invalid @enderror"
                       value="{{ is_string($name) ? $name : '' }}" maxlength="255" required autocomplete="name"
                       aria-describedby="profile-name-error" @error('name') aria-invalid="true" @enderror>
                <div id="profile-name-error">
                    @error('name')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label required" for="profile-email">{{ __('ui.email') }}</label>
                <input id="profile-email" name="email" type="email" dir="ltr" class="form-control @error('email') is-invalid @enderror"
                       value="{{ is_string($email) ? $email : '' }}" maxlength="255" required autocomplete="username"
                       aria-describedby="profile-email-error" @error('email') aria-invalid="true" @enderror>
                <div id="profile-email-error">
                    @error('email')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                </div>
            </div>
            @if($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && !$user->hasVerifiedEmail())
                <p class="text-secondary">{{ __('profile_ui.unverified') }}</p>
                <button type="submit" form="send-verification" class="btn btn-outline-secondary mb-3">{{ __('profile_ui.resend') }}</button>
                @if(session('status') === 'verification-link-sent')
                    <x-dashboard.alert type="success">{{ __('profile_ui.verification_sent') }}</x-dashboard.alert>
                @endif
            @endif
            <button type="submit" class="btn btn-primary">{{ __('profile_ui.save') }}</button>
        </form>
    </div>
</section>
