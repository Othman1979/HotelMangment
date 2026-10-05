<x-layouts.app :title="__('Login')">
    <div class="login-wrap">
        <div class="login-card">
            <div class="login-hero">
                <span class="hero-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18M5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16M9 7h.01M15 7h.01M9 11h.01M15 11h.01M10 21v-4h4v4"/></svg>
                </span>
                <h2>{{ __('Hotel Management') }}</h2>
                <p>{{ __('Reservations, front office, guest accounts, outlets and night audit in one system.') }}</p>
            </div>
            <div class="login-form-side">
                <h3>{{ __('Login') }}</h3>
                <form method="post" action="{{ route('login') }}">
                    @csrf
                    <div class="mb-3">
                        <label for="username" class="form-label">{{ __('Username') }}</label>
                        <input id="username" name="username" value="{{ old('username') }}" class="form-control" autocomplete="username" autocapitalize="none" autofocus required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">{{ __('Password') }}</label>
                        <input id="password" name="password" type="password" class="form-control" autocomplete="current-password" required>
                    </div>
                    <div class="form-check mb-4">
                        <input id="remember" name="remember" type="checkbox" value="1" class="form-check-input" checked>
                        <label for="remember" class="form-check-label">{{ __('Remember me') }}</label>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-2">{{ __('Login') }}</button>
                </form>
            </div>
        </div>
    </div>
</x-layouts.app>
