@extends('layouts.app')

@section('content')
@include('auth.partials.styles')
@include('auth.partials.reset-styles')

<main class="lg">
    <div class="fp">
        <div class="fp-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 2l-2 2m-7.6 7.6a5.5 5.5 0 1 1-7.8 7.8 5.5 5.5 0 0 1 7.8-7.8Zm0 0L15.5 7.5m0 0 3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg></div>

        @if (session('status'))
            <span class="fp-step">Step 2 of 3</span>
            <h1>Check your email</h1>
            <div class="fp-success" role="status">
                <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
                <span>{{ session('status') }}</span>
            </div>
            <ul class="fp-hints">
                <li>Open the email from <strong>{{ config('app.name') }}</strong> and click <strong>Reset password</strong>.</li>
                <li>Don't see it? Check your spam or promotions folder.</li>
                <li>Signed up with Google? You can also just use <strong>Continue with Google</strong>.</li>
            </ul>
            @if (app()->isLocal() && config('mail.default') === 'log')
                <div class="fp-dev" role="note"><strong>Developer note:</strong> email delivery isn't configured yet (<code>MAIL_MAILER=log</code>), so the reset email was written to <code>storage/logs/laravel.log</code> instead of being sent.</div>
            @endif
            <form method="POST" action="{{ route('password.email') }}">
                @csrf
                <input type="hidden" name="email" value="{{ session('reset_email') }}">
                <button class="fp-secondary" type="submit">Didn't get it? Send again</button>
            </form>
        @else
            <span class="fp-step">Step 1 of 3</span>
            <h1>Forgot your password?</h1>
            <p>Enter the email you sign in with and we'll send you a secure link to choose a new password.</p>

            @if ($errors->any())
                <div class="lg-alert" role="alert"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16.5v.01"/></svg><span>{{ $errors->first() }}</span></div>
            @endif

            <form method="POST" action="{{ route('password.email') }}" id="forgot-form" novalidate>
                @csrf
                <div class="lg-field">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
                    <input class="lg-input" id="email" name="email" type="email" value="{{ old('email') }}" placeholder=" " autocomplete="email" required autofocus @error('email') aria-invalid="true" @enderror>
                    <label for="email">Email address</label>
                </div>
                <button class="lg-submit" id="forgot-submit" type="submit"><span>Send reset link</span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg><span class="lg-spinner" aria-hidden="true"></span></button>
            </form>
        @endif

        <p class="fp-back"><a href="{{ route('login') }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 12H5M11 18l-6-6 6-6"/></svg> Back to sign in</a></p>
    </div>
</main>
<script>
    (() => {
        const form = document.getElementById('forgot-form');
        form?.addEventListener('submit', (event) => {
            if (! form.checkValidity()) { event.preventDefault(); form.reportValidity(); return; }
            document.getElementById('forgot-submit').classList.add('is-loading');
        });
    })();
</script>
@endsection
