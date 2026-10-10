@extends('layouts.app')

@section('content')
@include('auth.partials.styles')

<main class="lg">
    <div class="lg-shell">
        <section class="lg-brand" aria-label="About Acid Reflux Tracker">
            <div class="lg-aurora" aria-hidden="true"><i></i><i></i><i></i></div>

            <div class="lg-brand-top">
                <a class="lg-logo" href="{{ route('home') }}">
                    <img src="{{ asset('images/logo1.png') }}" alt="">
                    <span><strong>Acid Reflux Tracker</strong><small>PERSONAL HEALTH WORKSPACE</small></span>
                </a>
                <span class="lg-badge"><i></i> Private acid-safe journey</span>
            </div>

            <div class="lg-hero">
                <h1>Understand your body, <em>manage your health.</em></h1>
                <p>Track your food, symptoms, sleep, and health patterns to discover what triggers your acid reflux and take control of your well-being.</p>
            </div>

            <div class="lg-stage" aria-hidden="true">
                <div class="lg-float is-ring">
                    <span class="lg-ring"><svg viewBox="0 0 40 40"><circle class="track" cx="20" cy="20" r="16" pathLength="100"/><circle class="value" cx="20" cy="20" r="16" pathLength="100"/></svg><b>5/7</b></span>
                    <span><strong>Calm days</strong><span>This week</span></span>
                </div>

                <div class="lg-widget" id="lg-widget">
                    <div class="lg-widget-head">
                        <div><img src="{{ asset('images/logo1.png') }}" alt=""> Your health timeline</div>
                        <span class="lg-chip">7-day view</span>
                    </div>
                    <div class="lg-item">
                        <span class="lg-icon is-food"><svg viewBox="0 0 24 24"><path d="M7 2v20"/><path d="M4 2v6a3 3 0 0 0 6 0V2"/><path d="M17 22V2c-2.2 0-4 2.7-4 6v5h4"/></svg></span>
                        <div class="lg-item-copy"><strong>Food &amp; symptom timeline</strong><span>Log patterns as they happen</span></div>
                        <span class="lg-tag is-warn">Trigger</span>
                    </div>
                    <div class="lg-item">
                        <span class="lg-icon is-sleep"><svg viewBox="0 0 24 24"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z"/></svg></span>
                        <div class="lg-item-copy"><strong>Sleep &amp; stress tracking</strong><span>See what affects your day</span></div>
                        <span class="lg-tag is-ok">Rested</span>
                    </div>
                    <div class="lg-item">
                        <span class="lg-icon is-insight"><svg viewBox="0 0 24 24"><path d="M3 3v18h18"/><path d="m7 15 4-4 3 3 5-6"/></svg></span>
                        <div class="lg-item-copy"><strong>Weekly pattern insight</strong><span>Personalized summaries from your logs</span></div>
                        <span class="lg-bars"><i style="height:40%"></i><i style="height:65%"></i><i style="height:50%"></i><i class="is-high" style="height:90%"></i><i style="height:55%"></i><i style="height:35%"></i><i class="is-high" style="height:75%"></i></span>
                    </div>
                </div>

                <div class="lg-float is-toast">
                    <span class="lg-toast-dot"><svg viewBox="0 0 24 24"><path d="m5 12 5 5 9-10"/></svg></span>
                    <span><strong>Pattern found</strong><span>Late meals → night symptoms</span></span>
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
                <span>New here?</span>
                <a class="lg-ghost" href="{{ route('register') }}">Create account <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
            </div>

            <div class="lg-form">
                <span class="lg-eyebrow">Patient sign in</span>
                <h2>Welcome back</h2>
                <p class="lg-intro">Sign in to continue tracking your health and reviewing your patterns.</p>

                @if ($errors->any())
                    <div class="lg-alert" role="alert">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16.5v.01"/></svg>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                <a class="lg-google" href="{{ route('google.redirect') }}">
                    <svg viewBox="0 0 48 48" aria-hidden="true"><path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3C33.7 32.7 29.2 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.8 1.2 7.9 3.1l5.7-5.7C34 6.1 29.3 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20 20-8.9 20-20c0-1.3-.1-2.4-.4-3.5z"/><path fill="#FF3D00" d="m6.3 14.7 6.6 4.8C14.7 15.1 19 12 24 12c3.1 0 5.8 1.2 7.9 3.1l5.7-5.7C34 6.1 29.3 4 24 4 16.3 4 9.7 8.3 6.3 14.7z"/><path fill="#4CAF50" d="M24 44c5.2 0 9.9-2 13.4-5.2l-6.2-5.2C29.2 35.1 26.7 36 24 36c-5.2 0-9.6-3.3-11.3-8l-6.5 5C9.5 39.6 16.2 44 24 44z"/><path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3c-.8 2.2-2.2 4.2-4.1 5.6l6.2 5.2C37 39.2 44 34 44 24c0-1.3-.1-2.4-.4-3.5z"/></svg>
                    Continue with Google
                </a>

                <div class="lg-divider">or sign in with email</div>

                <form method="POST" action="{{ route('login.submit') }}" id="login-form" novalidate>
                    @csrf
                    <div class="lg-field">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
                        <input class="lg-input" id="email" name="email" type="email" value="{{ old('email') }}" placeholder=" " autocomplete="email" required autofocus @error('email') aria-invalid="true" @enderror>
                        <label for="email">Email address</label>
                    </div>

                    <div class="lg-field">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
                        <input class="lg-input has-toggle" id="password" name="password" type="password" placeholder=" " autocomplete="current-password" required @error('password') aria-invalid="true" @enderror>
                        <label for="password">Password</label>
                        <button class="password-toggle" id="toggle-password" type="button" aria-label="Show password" title="Show password" aria-pressed="false" aria-controls="password"><svg class="eye-closed" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3l18 18"></path><path d="M10.6 6.2A10.7 10.7 0 0 1 12 6c6.5 0 10 6 10 6a18.8 18.8 0 0 1-3.1 3.6"></path><path d="M6.1 6.1C3.5 7.8 2 12 2 12s3.5 6 10 6a10.5 10.5 0 0 0 4.1-.8"></path></svg><svg class="eye-open" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"></path><circle cx="12" cy="12" r="2.5"></circle></svg></button>
                        <p class="lg-caps" id="caps-warning" role="status">⇪ Caps Lock is on</p>
                    </div>

                    <div class="lg-options">
                        <label class="lg-check"><input type="checkbox" name="remember" @checked(old('remember'))><span class="lg-check-box"><svg viewBox="0 0 24 24"><path d="m5 12 5 5 9-10"/></svg></span> Remember me</label>
                        <a class="lg-link" href="#">Forgot password?</a>
                    </div>

                    <button class="lg-submit" id="login-submit" type="submit">
                        <span>Sign in securely</span>
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                        <span class="lg-spinner" aria-hidden="true"></span>
                    </button>
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
        const password = document.getElementById('password');
        const toggle = document.getElementById('toggle-password');
        const capsWarning = document.getElementById('caps-warning');
        const form = document.getElementById('login-form');
        const submit = document.getElementById('login-submit');
        const widget = document.getElementById('lg-widget');
        const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        toggle?.addEventListener('click', function () {
            const showing = password.type === 'text';
            password.type = showing ? 'password' : 'text';
            this.classList.toggle('is-visible', !showing);
            this.setAttribute('aria-pressed', String(!showing));
            this.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
            this.setAttribute('title', showing ? 'Show password' : 'Hide password');
            password.focus();
        });

        ['keydown', 'keyup'].forEach((eventName) => password?.addEventListener(eventName, (event) => {
            capsWarning.classList.toggle('is-on', event.getModifierState && event.getModifierState('CapsLock'));
        }));
        password?.addEventListener('blur', () => capsWarning.classList.remove('is-on'));

        form?.addEventListener('submit', (event) => {
            if (! form.checkValidity()) {
                event.preventDefault();
                form.reportValidity();
                return;
            }
            submit.classList.add('is-loading');
            submit.setAttribute('aria-busy', 'true');
        });

        const brand = document.querySelector('.lg-brand');
        if (widget && brand && ! prefersReducedMotion && window.matchMedia('(pointer: fine)').matches) {
            brand.addEventListener('pointermove', (event) => {
                const bounds = brand.getBoundingClientRect();
                const x = (event.clientX - bounds.left) / bounds.width - .5;
                const y = (event.clientY - bounds.top) / bounds.height - .5;
                widget.style.transform = `rotateY(${x * 8}deg) rotateX(${y * -8}deg)`;
            });
            brand.addEventListener('pointerleave', () => { widget.style.transform = ''; });
        }
    })();
</script>
@endsection
