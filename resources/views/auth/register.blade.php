@extends('layouts.app')

@section('content')
<div class="auth-bg">
    <nav class="auth-nav"><a class="brand" href="{{ route('home') }}"><img class="brand-logo" src="{{ asset('images/logo1.png') }}" alt="RefluxCare logo"><span>RefluxCare<small>HEALTH &amp; TRIGGER LAB</small></span></a><a href="{{ route('login') }}" class="pill">Already have an account? <strong>Sign in</strong></a></nav>
    <div class="auth-grid" style="margin-top:45px">
        <section class="auth-copy"><div class="eyebrow"><span class="dot"></span> START YOUR ACID-SAFE JOURNEY</div><h1>Create a calmer health <span>routine.</span></h1><p>Your account keeps your logs private and gives the insight engine enough context to spot patterns over time.</p><div class="feature-row"><span class="feature">Food + symptom timeline</span><span class="feature">Sleep &amp; stress tracking</span><span class="feature">Personal recommendations</span></div></section>
        <section class="auth-card"><h2>Create your account</h2><p class="subtle">It takes less than a minute. You can also use Google.</p>@if($errors->any())<div class="error">{{ $errors->first() }}</div>@endif
            <form method="POST" action="{{ route('register.submit') }}">@csrf
                <div class="field"><label for="name">Full name</label><input id="name" name="name" value="{{ old('name') }}" placeholder="Your name" required></div>
                <div class="field"><label for="email">Email address</label><input id="email" name="email" type="email" value="{{ old('email') }}" placeholder="you@example.com" required></div>
                <div class="field"><label for="password">Password</label><input id="password" name="password" type="password" placeholder="At least 8 characters" required></div>
                <div class="field"><label for="password_confirmation">Confirm password</label><input id="password_confirmation" name="password_confirmation" type="password" placeholder="Repeat your password" required></div>
                <button class="btn" type="submit">Create patient account →</button>
            </form>
            <div class="divider">or</div><a class="btn google" href="{{ route('google.redirect') }}">G <span>Sign up with Google</span></a>
        </section>
    </div>
</div>
@endsection
