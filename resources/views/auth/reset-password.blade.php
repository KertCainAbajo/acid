@extends('layouts.app')

@section('content')
@php
    $eyeIcons = '<svg class="eye-closed" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3l18 18"/><path d="M10.6 6.2A10.7 10.7 0 0 1 12 6c6.5 0 10 6 10 6a18.8 18.8 0 0 1-3.1 3.6"/><path d="M6.1 6.1C3.5 7.8 2 12 2 12s3.5 6 10 6a10.5 10.5 0 0 0 4.1-.8"/></svg><svg class="eye-open" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="2.5"/></svg>';
@endphp
@include('auth.partials.styles')
@include('auth.partials.reset-styles')

<main class="lg">
    <div class="fp">
        <div class="fp-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg></div>
        <span class="fp-step">Step 3 of 3</span>
        <h1>Choose a new password</h1>
        <p>Pick a strong password you don't use anywhere else. You'll be signed out on every other device.</p>

        @if ($errors->has('email') || $errors->has('token'))
            <div class="lg-alert" role="alert"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16.5v.01"/></svg><span>{{ $errors->first('email') ?: $errors->first('token') }} <a class="lg-link" href="{{ route('password.request') }}">Request a new link</a></span></div>
        @endif

        <form method="POST" action="{{ route('password.update') }}" id="reset-form" novalidate>
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div class="lg-field">
                <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
                <input class="lg-input" id="email" name="email" type="email" value="{{ old('email', $email) }}" placeholder=" " autocomplete="email" required @if (! old('email', $email)) autofocus @endif>
                <label for="email">Email address</label>
            </div>

            <div class="lg-field">
                <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
                <input class="lg-input has-toggle" id="password" name="password" type="password" placeholder=" " autocomplete="new-password" required @if (old('email', $email)) autofocus @endif @error('password') aria-invalid="true" @enderror>
                <label for="password">New password</label>
                <button class="password-toggle" type="button" data-toggle="password" aria-label="Show password">{!! $eyeIcons !!}</button>
            </div>
            @error('password')<p class="fp-error">{{ $message }}</p>@enderror
            <ul class="fp-rules" aria-label="Password requirements">
                <li data-rule="length"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 5 5 9-10"/></svg> At least 8 characters</li>
                <li data-rule="letters"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 5 5 9-10"/></svg> Contains letters</li>
                <li data-rule="number"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 5 5 9-10"/></svg> Contains a number</li>
                <li data-rule="match"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 5 5 9-10"/></svg> Passwords match</li>
            </ul>

            <div class="lg-field">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 4 6v6c0 5 3.4 8.4 8 9 4.6-.6 8-4 8-9V6l-8-3Z"/><path d="m9 12 2 2 4-4"/></svg>
                <input class="lg-input has-toggle" id="password_confirmation" name="password_confirmation" type="password" placeholder=" " autocomplete="new-password" required>
                <label for="password_confirmation">Confirm new password</label>
                <button class="password-toggle" type="button" data-toggle="password_confirmation" aria-label="Show password">{!! $eyeIcons !!}</button>
            </div>

            <button class="lg-submit" id="reset-submit" type="submit"><span>Reset password</span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg><span class="lg-spinner" aria-hidden="true"></span></button>
        </form>

        <p class="fp-back"><a href="{{ route('login') }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 12H5M11 18l-6-6 6-6"/></svg> Back to sign in</a></p>
    </div>
</main>
<script>
    (() => {
        document.querySelectorAll('[data-toggle]').forEach((button) => button.addEventListener('click', () => {
            const input = document.getElementById(button.dataset.toggle);
            const showing = input.type === 'text';
            input.type = showing ? 'password' : 'text';
            button.classList.toggle('is-visible', ! showing);
            button.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
        }));

        const password = document.getElementById('password');
        const confirmation = document.getElementById('password_confirmation');
        const rules = {
            length: () => password.value.length >= 8,
            letters: () => /[A-Za-z]/.test(password.value),
            number: () => /\d/.test(password.value),
            match: () => password.value !== '' && password.value === confirmation.value,
        };
        const update = () => Object.entries(rules).forEach(([rule, test]) => document.querySelector(`[data-rule="${rule}"]`).classList.toggle('is-met', test()));
        [password, confirmation].forEach((input) => input.addEventListener('input', update));

        const form = document.getElementById('reset-form');
        form.addEventListener('submit', (event) => {
            if (! form.checkValidity()) { event.preventDefault(); form.reportValidity(); return; }
            document.getElementById('reset-submit').classList.add('is-loading');
        });
    })();
</script>
@endsection
