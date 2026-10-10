@extends('layouts.app')

@section('content')
@include('auth.partials.styles')
<style>
    .rg-error { margin:7px 0 0 4px; color:#b91c1c; font-size:12.5px; font-weight:600; }
    .rg-error:empty { display:none; }
    .rg-strength { margin:10px 2px 0; }
    .rg-strength-bars { display:grid; grid-template-columns:repeat(4,1fr); gap:5px; }
    .rg-strength-bars i { height:5px; border-radius:999px; background:#e2e8f0; transition:background .25s ease; }
    .rg-strength[data-level="1"] i:nth-child(-n+1) { background:#ef4444; }
    .rg-strength[data-level="2"] i:nth-child(-n+2) { background:#f59e0b; }
    .rg-strength[data-level="3"] i:nth-child(-n+3) { background:#22c55e; }
    .rg-strength[data-level="4"] i:nth-child(-n+4) { background:#059669; }
    .rg-strength-top { display:flex; justify-content:space-between; gap:10px; margin-top:7px; color:#64748b; font-size:12px; }
    .rg-strength-top strong { color:#334155; }
    .rg-checklist { display:grid; grid-template-columns:1fr 1fr; gap:5px 12px; margin:10px 0 0; padding:0; list-style:none; }
    .rg-checklist li { display:flex; align-items:center; gap:7px; color:#94a3b8; font-size:12px; transition:color .2s ease; }
    .rg-checklist li svg { width:14px; height:14px; flex:none; padding:2px; border-radius:50%; fill:none; stroke:#fff; stroke-width:3.4; stroke-linecap:round; stroke-linejoin:round; background:#e2e8f0; transition:background .2s ease; }
    .rg-checklist li.is-met { color:#065f46; }
    .rg-checklist li.is-met svg { background:#10b981; }
    .rg-checklist li em { color:#dc2626; font-style:normal; }
    .rg-match { display:none; align-items:center; gap:6px; margin:8px 0 0 4px; font-size:12.5px; font-weight:600; }
    .rg-match.is-ok { display:flex; color:#047857; }
    .rg-match.is-bad { display:flex; color:#b91c1c; }
    .rg-match svg { width:14px; height:14px; fill:none; stroke:currentColor; stroke-width:2.6; stroke-linecap:round; stroke-linejoin:round; }
    .rg-private { display:flex; align-items:center; justify-content:center; gap:7px; margin:16px 0 0; color:#64748b; font-size:12.5px; text-align:center; }
    .rg-private svg { width:14px; height:14px; flex:none; fill:none; stroke:var(--brand); stroke-width:2.2; stroke-linecap:round; stroke-linejoin:round; }
    .rg-steps .lg-item-copy span { white-space:normal; }
    .rg-step-num { width:24px; height:24px; flex:none; display:grid; place-items:center; border-radius:50%; color:#065f46; background:#d1fae5; font-size:11px; font-weight:800; }
    @media (max-width:560px) { .rg-checklist { grid-template-columns:1fr; } }
    .rg-brand { justify-content:space-between; gap:26px; }
    .rg-brand .lg-hero { margin-top:0; }
    .rg-brand .lg-stage { width:100%; margin:0; padding:14px 0 0; }
    .rg-brand .lg-float.is-ring { top:-12px; right:-4px; }
    .rg-benefits { display:grid; grid-template-columns:1fr 1fr; gap:10px; margin:0; padding:0; list-style:none; }
    .rg-benefits li { display:flex; align-items:flex-start; gap:11px; padding:13px; border:1px solid #ffffff1f; border-radius:16px; background:#ffffff0f; backdrop-filter:blur(10px); transition:background .2s ease,transform .2s ease; }
    .rg-benefits li:hover { background:#ffffff1a; transform:translateY(-2px); }
    .rg-benefits span { width:34px; height:34px; flex:none; display:grid; place-items:center; border-radius:10px; background:#34d39926; }
    .rg-benefits svg { width:17px; height:17px; fill:none; stroke:#6ee7b7; stroke-width:2; stroke-linecap:round; stroke-linejoin:round; }
    .rg-benefits strong { display:block; color:#fff; font-size:13px; }
    .rg-benefits small { display:block; margin-top:2px; color:#a9cbbf; font-size:11.5px; line-height:1.4; }
    @media (max-width:560px) { .rg-benefits { grid-template-columns:1fr; } }
</style>

<main class="lg">
    <div class="lg-shell">
        <section class="lg-brand rg-brand" aria-label="About RefluxCare">
            <div class="lg-aurora" aria-hidden="true"><i></i><i></i><i></i></div>

            <div class="lg-brand-top">
                <a class="lg-logo" href="{{ route('home') }}">
                    <img src="{{ asset('images/logo1.png') }}" alt="">
                    <span><strong>Acid Reflux Tracker</strong><small>PERSONAL HEALTH WORKSPACE</small></span>
                </a>
                <span class="lg-badge"><i></i> Start your acid-safe journey</span>
            </div>

            <div class="lg-hero">
                <h1>Create a calmer <em>health routine.</em></h1>
                <p>Your private account keeps every meal, symptom and night of sleep in one place, so RefluxCare can spot the patterns behind your reflux.</p>
            </div>

            <ul class="rg-benefits" aria-label="What you get">
                <li><span><svg viewBox="0 0 24 24"><path d="M3 3v18h18"/><path d="m7 15 4-4 3 3 5-6"/></svg></span><div><strong>Trigger analysis</strong><small>See which foods are followed by symptoms</small></div></li>
                <li><span><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></span><div><strong>Explainable risk score</strong><small>Know exactly what raises your risk</small></div></li>
                <li><span><svg viewBox="0 0 24 24"><path d="M12 3v12M7 10l5 5 5-5"/><path d="M4 21h16"/></svg></span><div><strong>Doctor-ready export</strong><small>Download your full history as CSV</small></div></li>
                <li><span><svg viewBox="0 0 24 24"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg></span><div><strong>100% private</strong><small>Only you can see your logs</small></div></li>
            </ul>

            <div class="lg-stage rg-steps" aria-hidden="true">
                <div class="lg-float is-ring">
                    <span class="lg-ring"><svg viewBox="0 0 40 40"><circle class="track" cx="20" cy="20" r="16" pathLength="100"/><circle class="value" cx="20" cy="20" r="16" pathLength="100"/></svg><b>1 min</b></span>
                    <span><strong>Quick setup</strong><span>No card needed</span></span>
                </div>

                <div class="lg-widget" id="lg-widget">
                    <div class="lg-widget-head">
                        <div><img src="{{ asset('images/logo1.png') }}" alt=""> How it works</div>
                    </div>
                    <div class="lg-item">
                        <span class="lg-icon is-food"><svg viewBox="0 0 24 24"><path d="M7 2v20"/><path d="M4 2v6a3 3 0 0 0 6 0V2"/><path d="M17 22V2c-2.2 0-4 2.7-4 6v5h4"/></svg></span>
                        <div class="lg-item-copy"><strong>Log your day</strong><span>Meals, symptoms, sleep and stress in seconds</span></div>
                        <span class="rg-step-num">1</span>
                    </div>
                    <div class="lg-item">
                        <span class="lg-icon is-insight"><svg viewBox="0 0 24 24"><path d="M3 3v18h18"/><path d="m7 15 4-4 3 3 5-6"/></svg></span>
                        <div class="lg-item-copy"><strong>Discover your triggers</strong><span>See which foods are followed by symptoms</span></div>
                        <span class="rg-step-num">2</span>
                    </div>
                    <div class="lg-item">
                        <span class="lg-icon is-sleep"><svg viewBox="0 0 24 24"><path d="M12 3 4 6v6c0 5 3.4 8.4 8 9 4.6-.6 8-4 8-9V6l-8-3Z"/><path d="m9 12 2 2 4-4"/></svg></span>
                        <div class="lg-item-copy"><strong>Follow your plan</strong><span>Personal steps built from your own data</span></div>
                        <span class="rg-step-num">3</span>
                    </div>
                </div>

            </div>

            <div class="lg-brand-foot">
                <span><svg viewBox="0 0 24 24"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg> Your data stays private</span>
                <a href="{{ route('privacy') }}">Privacy Policy</a>
                <a href="{{ route('terms') }}">Terms of Service</a>
            </div>
        </section>

        <section class="lg-panel">
            <div class="lg-panel-top">
                <span>Already have an account?</span>
                <a class="lg-ghost" href="{{ route('login') }}">Sign in <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
            </div>

            <div class="lg-form">
                <span class="lg-eyebrow">New patient</span>
                <h2>Create your account</h2>
                <p class="lg-intro">It takes less than a minute. Start logging right after you sign up.</p>

                @if ($errors->any())
                    <div class="lg-alert" role="alert">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16.5v.01"/></svg>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                <a class="lg-google" href="{{ route('google.redirect') }}">
                    <svg viewBox="0 0 48 48" aria-hidden="true"><path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3C33.7 32.7 29.2 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.8 1.2 7.9 3.1l5.7-5.7C34 6.1 29.3 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20 20-8.9 20-20c0-1.3-.1-2.4-.4-3.5z"/><path fill="#FF3D00" d="m6.3 14.7 6.6 4.8C14.7 15.1 19 12 24 12c3.1 0 5.8 1.2 7.9 3.1l5.7-5.7C34 6.1 29.3 4 24 4 16.3 4 9.7 8.3 6.3 14.7z"/><path fill="#4CAF50" d="M24 44c5.2 0 9.9-2 13.4-5.2l-6.2-5.2C29.2 35.1 26.7 36 24 36c-5.2 0-9.6-3.3-11.3-8l-6.5 5C9.5 39.6 16.2 44 24 44z"/><path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3c-.8 2.2-2.2 4.2-4.1 5.6l6.2 5.2C37 39.2 44 34 44 24c0-1.3-.1-2.4-.4-3.5z"/></svg>
                    Sign up with Google
                </a>

                <div class="lg-divider">or sign up with email</div>

                <form method="POST" action="{{ route('register.submit') }}" id="register-form" novalidate>
                    @csrf
                    <div class="lg-field">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
                        <input class="lg-input" id="name" name="name" value="{{ old('name') }}" placeholder=" " autocomplete="name" maxlength="120" required autofocus aria-describedby="name-error" @error('name') aria-invalid="true" @enderror>
                        <label for="name">Full name</label>
                        <p class="rg-error" id="name-error" data-error-for="name">@error('name'){{ $message }}@enderror</p>
                    </div>

                    <div class="lg-field">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
                        <input class="lg-input" id="email" name="email" type="email" value="{{ old('email') }}" placeholder=" " autocomplete="email" maxlength="255" required aria-describedby="email-error" @error('email') aria-invalid="true" @enderror>
                        <label for="email">Email address</label>
                        <p class="rg-error" id="email-error" data-error-for="email">@error('email'){{ $message }}@enderror</p>
                    </div>

                    <div class="lg-field">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
                        <input class="lg-input has-toggle" id="password" name="password" type="password" placeholder=" " autocomplete="new-password" minlength="8" required aria-describedby="password-strength password-error" @error('password') aria-invalid="true" @enderror>
                        <label for="password">Password</label>
                        <button class="password-toggle" type="button" data-toggle-password="password" aria-label="Show password" title="Show password" aria-pressed="false" aria-controls="password"><svg class="eye-closed" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3l18 18"></path><path d="M10.6 6.2A10.7 10.7 0 0 1 12 6c6.5 0 10 6 10 6a18.8 18.8 0 0 1-3.1 3.6"></path><path d="M6.1 6.1C3.5 7.8 2 12 2 12s3.5 6 10 6a10.5 10.5 0 0 0 4.1-.8"></path></svg><svg class="eye-open" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"></path><circle cx="12" cy="12" r="2.5"></circle></svg></button>
                        <p class="lg-caps" id="caps-warning" role="status">⇪ Caps Lock is on</p>
                        <div class="rg-strength" id="password-strength" data-level="0" aria-live="polite">
                            <div class="rg-strength-bars" aria-hidden="true"><i></i><i></i><i></i><i></i></div>
                            <div class="rg-strength-top"><span>Password strength</span><strong id="strength-label">Not set</strong></div>
                            <ul class="rg-checklist">
                                <li data-rule="length"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 5 5 9-10"/></svg> 8+ characters <em>*</em></li>
                                <li data-rule="case"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 5 5 9-10"/></svg> Upper &amp; lowercase</li>
                                <li data-rule="number"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 5 5 9-10"/></svg> A number</li>
                                <li data-rule="symbol"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 5 5 9-10"/></svg> A symbol</li>
                            </ul>
                        </div>
                        <p class="rg-error" id="password-error" data-error-for="password">@error('password'){{ $message }}@enderror</p>
                    </div>

                    <div class="lg-field">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 4 6v6c0 5 3.4 8.4 8 9 4.6-.6 8-4 8-9V6l-8-3Z"/><path d="m9 12 2 2 4-4"/></svg>
                        <input class="lg-input has-toggle" id="password_confirmation" name="password_confirmation" type="password" placeholder=" " autocomplete="new-password" required aria-describedby="password-match">
                        <label for="password_confirmation">Confirm password</label>
                        <button class="password-toggle" type="button" data-toggle-password="password_confirmation" aria-label="Show password" title="Show password" aria-pressed="false" aria-controls="password_confirmation"><svg class="eye-closed" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3l18 18"></path><path d="M10.6 6.2A10.7 10.7 0 0 1 12 6c6.5 0 10 6 10 6a18.8 18.8 0 0 1-3.1 3.6"></path><path d="M6.1 6.1C3.5 7.8 2 12 2 12s3.5 6 10 6a10.5 10.5 0 0 0 4.1-.8"></path></svg><svg class="eye-open" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"></path><circle cx="12" cy="12" r="2.5"></circle></svg></button>
                        <p class="rg-match" id="password-match" role="status"></p>
                    </div>

                    <button class="lg-submit" id="register-submit" type="submit">
                        <span>Create my account</span>
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                        <span class="lg-spinner" aria-hidden="true"></span>
                    </button>
                    <p class="rg-private"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg> By creating an account you agree to our <a class="lg-link" href="{{ route('terms') }}">Terms</a> and <a class="lg-link" href="{{ route('privacy') }}">Privacy Policy</a>.</p>
                </form>

                <div class="lg-trust">
                    <div><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 4 6v6c0 5 3.4 8.4 8 9 4.6-.6 8-4 8-9V6l-8-3Z"/><path d="m9 12 2 2 4-4"/></svg><span>Private logs</span></div>
                    <div><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3v18h18"/><path d="m7 15 4-4 3 3 5-6"/></svg><span>Pattern insights</span></div>
                    <div><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.8 8.6a5.5 5.5 0 0 0-9.3-3.9L12 5.2l.5-.5a5.5 5.5 0 0 1 7.8 7.8L12 20.8l-8.3-8.3a5.5 5.5 0 0 1 7.8-7.8"/></svg><span>Personal care</span></div>
                </div>
            </div>
        </section>
    </div>
</main>
<script>
    (() => {
        const form = document.getElementById('register-form');
        const submit = document.getElementById('register-submit');
        const nameInput = document.getElementById('name');
        const email = document.getElementById('email');
        const password = document.getElementById('password');
        const confirmation = document.getElementById('password_confirmation');
        const strength = document.getElementById('password-strength');
        const strengthLabel = document.getElementById('strength-label');
        const match = document.getElementById('password-match');
        const capsWarning = document.getElementById('caps-warning');
        const widget = document.getElementById('lg-widget');
        const check = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 5 5 9-10"/></svg>';
        const cross = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>';

        document.querySelectorAll('[data-toggle-password]').forEach((toggle) => toggle.addEventListener('click', () => {
            const input = document.getElementById(toggle.dataset.togglePassword);
            const showing = input.type === 'text';
            input.type = showing ? 'password' : 'text';
            toggle.classList.toggle('is-visible', ! showing);
            toggle.setAttribute('aria-pressed', String(! showing));
            toggle.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
            toggle.setAttribute('title', showing ? 'Show password' : 'Hide password');
            input.focus();
        }));

        const setError = (field, message) => {
            const target = form.querySelector(`[data-error-for="${field}"]`);
            if (target) { target.textContent = message; }
            if (message === '') { form.elements[field]?.removeAttribute('aria-invalid'); } else { form.elements[field]?.setAttribute('aria-invalid', 'true'); }
        };

        const rules = {
            length: (value) => value.length >= 8,
            case: (value) => /[a-z]/.test(value) && /[A-Z]/.test(value),
            number: (value) => /\d/.test(value),
            symbol: (value) => /[^A-Za-z0-9]/.test(value),
        };
        const updateStrength = () => {
            const value = password.value;
            const met = Object.entries(rules).filter(([rule, test]) => {
                const isMet = test(value);
                strength.querySelector(`[data-rule="${rule}"]`).classList.toggle('is-met', isMet);
                return isMet;
            }).length;
            const level = value === '' ? 0 : (rules.length(value) ? Math.max(1, met) : 1);
            strength.dataset.level = String(level);
            strengthLabel.textContent = value === '' ? 'Not set' : (! rules.length(value) ? 'Too short' : ['Weak', 'Weak', 'Fair', 'Good', 'Strong'][level]);
        };
        const updateMatch = () => {
            if (confirmation.value === '') { match.className = 'rg-match'; match.innerHTML = ''; return; }
            const same = confirmation.value === password.value;
            match.className = `rg-match ${same ? 'is-ok' : 'is-bad'}`;
            match.innerHTML = `${same ? check : cross}<span>${same ? 'Passwords match' : 'Passwords do not match yet'}</span>`;
        };

        password.addEventListener('input', () => { updateStrength(); updateMatch(); setError('password', ''); });
        confirmation.addEventListener('input', updateMatch);
        nameInput.addEventListener('input', () => setError('name', ''));
        email.addEventListener('input', () => setError('email', ''));
        ['keydown', 'keyup'].forEach((eventName) => password.addEventListener(eventName, (event) => {
            capsWarning.classList.toggle('is-on', event.getModifierState && event.getModifierState('CapsLock'));
        }));
        password.addEventListener('blur', () => capsWarning.classList.remove('is-on'));

        form.addEventListener('submit', (event) => {
            const errors = {
                name: nameInput.value.trim() === '' ? 'Enter your name.' : '',
                email: ! email.validity.valid || email.value.trim() === '' ? 'Enter a valid email address.' : '',
                password: password.value.length < 8 ? 'Use at least 8 characters.' : (password.value !== confirmation.value ? 'Your passwords do not match.' : ''),
            };
            Object.entries(errors).forEach(([field, message]) => setError(field, message));
            const firstInvalid = Object.keys(errors).find((field) => errors[field] !== '');
            if (firstInvalid) {
                event.preventDefault();
                form.elements[firstInvalid].focus();
                return;
            }
            submit.classList.add('is-loading');
            submit.setAttribute('aria-busy', 'true');
        });

        updateStrength();

        const brand = document.querySelector('.lg-brand');
        if (widget && brand && ! window.matchMedia('(prefers-reduced-motion: reduce)').matches && window.matchMedia('(pointer: fine)').matches) {
            brand.addEventListener('pointermove', (event) => {
                const bounds = brand.getBoundingClientRect();
                widget.style.transform = `rotateY(${((event.clientX - bounds.left) / bounds.width - .5) * 8}deg) rotateX(${((event.clientY - bounds.top) / bounds.height - .5) * -8}deg)`;
            });
            brand.addEventListener('pointerleave', () => { widget.style.transform = ''; });
        }
    })();
</script>
@endsection
