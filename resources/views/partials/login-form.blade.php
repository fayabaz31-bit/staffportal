<form class="signin-form" method="POST" action="{{ route('login.store') }}">
    @csrf
    <div class="field-group">
        <label for="identifier">Staff number or institutional email</label>
        <input
            id="identifier"
            name="identifier"
            type="text"
            value="{{ old('identifier') }}"
            autocomplete="username"
            autocapitalize="none"
            spellcheck="false"
            aria-invalid="{{ $errors->has('identifier') ? 'true' : 'false' }}"
            aria-describedby="identifier-error"
            required
        >
        @error('identifier')
            <p class="field-error" id="identifier-error">{{ $message }}</p>
        @enderror
    </div>

    <div class="field-group">
        <label for="password">Password</label>
        <input id="password" name="password" type="password" autocomplete="current-password" required>
    </div>

    <label class="remember-option" for="remember">
        <input id="remember" name="remember" type="checkbox" value="1">
        <span>Keep me signed in on this device</span>
    </label>

    <button class="button button-primary" type="submit">Sign in <span aria-hidden="true">&#8594;</span></button>

    <p style="margin-top: 12px; font-size: 12px;"><a class="back-link" style="font-size: 12px;" href="{{ route('password.request') }}">Forgot your password?</a></p>

    @env(['local', 'testing'])
        <div class="demo-access">
            <label class="demo-label" for="demo-role">LOCAL PREVIEW ACCOUNT</label>
            <select class="demo-role-select" id="demo-role" aria-label="Choose a demo role">
                @foreach (\App\UserRole::cases() as $demoRole)
                    <option value="{{ $demoRole->demoStaffNumber() }}">{{ $demoRole->label() }} · {{ $demoRole->demoStaffNumber() }}</option>
                @endforeach
            </select>
            <button class="demo-use-button" type="button" data-fill-demo>Use account</button>
            <p class="demo-password" id="demo-access-status" aria-live="polite">Shared password: <code>password</code></p>
        </div>
    @endenv
</form>