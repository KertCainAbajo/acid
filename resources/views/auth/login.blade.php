@extends('layouts.app')

@section('content')
<style>
    .simple-login { min-height:100vh; display:flex; align-items:center; justify-content:center; padding:32px; background:linear-gradient(135deg,#ecf8f4,#f5f8fb); color:#11192c; }
    .simple-login-card { width:min(1120px,100%); min-height:680px; display:grid; grid-template-columns:1fr 1fr; overflow:hidden; border:1px solid #dcebe6; border-radius:25px; background:#fff; box-shadow:0 24px 70px #195b4820; }
    .simple-brand-panel { position:relative; display:flex; flex-direction:column; justify-content:space-between; min-height:100%; padding:42px 7vw 34px 4vw; overflow:visible; isolation:isolate; background:radial-gradient(circle at 80% 55%,#a9f5d8 0,#d7f9e9 30%,transparent 64%),linear-gradient(135deg,#dcf9eb,#effffc 65%,#f8fffd); }
    .simple-brand-panel:before { content:""; position:absolute; top:12%; right:5%; z-index:0; width:180px; height:180px; border:1px solid #74d9b044; border-radius:50%; box-shadow:0 0 0 24px #ffffff20,0 0 0 48px #ffffff16; }
    .simple-brand-panel:after { content:""; position:absolute; bottom:10%; left:7%; z-index:0; width:100px; height:100px; border-radius:28px; background:#ffffff35; transform:rotate(18deg); }
    .simple-brand-panel > * { position:relative; z-index:1; }
    .brand-header { display:flex; flex-direction:column; align-items:flex-start; gap:10px; }
    .simple-brand { display:flex; align-items:center; gap:10px; color:#123d36; font-weight:800; font-size:15px; letter-spacing:-.035em; }
    .simple-brand-logo { width:38px; height:38px; display:block; object-fit:contain; border-radius:11px; box-shadow:0 6px 14px #17684f2b; }
    .brand-copy { display:flex; flex-direction:column; gap:2px; }
    .brand-copy strong { color:#123d36; }
    .brand-copy small { color:#4f7c71; font-size:8px; letter-spacing:.09em; }
    .simple-copy { max-width:500px; margin-top:0; }
    .brand-kicker { display:inline-flex; align-items:center; gap:8px; margin:0; padding:6px 10px; border:1px solid #9ce6c7; border-radius:999px; color:#17684f; background:#ffffff75; font-size:10px; font-weight:800; letter-spacing:.04em; }
    .brand-kicker-dot { width:7px; height:7px; border-radius:50%; background:#0aa56a; box-shadow:0 0 0 4px #0aa56a22; }
    .simple-copy h1 { margin:0 0 26px; font-size:clamp(38px,3.6vw,58px); line-height:1.03; letter-spacing:-.065em; }
    .simple-copy h1 .soft { color:#11192c; }
    .simple-copy h1 .green { color:#17684f; }
    .simple-copy p { max-width:430px; margin:0; color:#486276; font-size:15px; line-height:1.55; }
    .simple-visual { width:min(100%,385px); min-height:242px; margin-top:27px; padding:14px; border:1px solid #fff; border-radius:15px; background:#ffffff59; box-shadow:0 18px 40px #63c8a524; }
    .visual-top { display:flex; align-items:center; gap:9px; height:28px; }
    .visual-logo { width:24px; height:24px; display:block; padding:3px; object-fit:contain; border-radius:50%; background:#d9f8ea; }
    .visual-line { flex:1; width:auto; height:auto; color:#123d36; font-size:10px; font-weight:750; }
    .visual-top:after { content:"7-day view"; padding:4px 7px; border-radius:999px; color:#19805f; background:#e3faef; font-size:8px; font-weight:800; }
    .visual-panel { min-height:47px; display:flex; align-items:center; gap:10px; margin-top:10px; padding:10px 12px; border-radius:11px; background:#fff; box-shadow:0 2px 8px #84adad22; transition:.2s ease; }
    .visual-panel:hover { transform:translateX(4px); box-shadow:0 7px 16px #84adad25; }
    .visual-panel div { flex:1; color:#486b6e; font-size:10px; line-height:1.3; }
    .visual-panel strong { display:block; color:#173e47; font-size:11px; }
    .visual-panel em { display:inline-block; margin-top:3px; color:#1c9d6c; font-style:normal; font-size:9px; }
    .visual-large { min-height:76px; display:flex; align-items:center; justify-content:space-between; gap:12px; margin-top:10px; padding:12px; border-radius:11px; background:#fff; box-shadow:0 2px 8px #84adad22; }
    .visual-large strong { display:block; color:#173e47; font-size:11px; }
    .visual-large span { display:block; margin-top:4px; color:#628080; font-size:9px; }
    .visual-score { color:#0aa56a; font-size:20px; font-weight:800; }
    .simple-legal { width:min(100%,385px); display:flex; align-items:center; justify-content:flex-start; gap:18px; margin-top:24px; padding-top:14px; border-top:1px solid #b9e5d5; color:#5a7772; font-size:10px; }
    .simple-legal a { color:#315f57; font-weight:700; }
    .simple-legal a:hover { color:#0aa56a; text-decoration:underline; }
    .simple-form-panel { position:relative; display:flex; align-items:center; justify-content:center; padding:44px 7vw; overflow:hidden; background:linear-gradient(145deg,#fff 35%,#fbfffd 100%); }
    .simple-form-panel:before { content:""; position:absolute; width:360px; height:360px; top:-190px; right:-140px; border-radius:50%; background:#dff8ec; opacity:.75; }
    .simple-form-panel:after { content:""; position:absolute; width:180px; height:180px; bottom:-95px; left:-75px; border:28px solid #edf9f3; border-radius:50%; }
    .simple-form { position:relative; z-index:1; width:min(100%,390px); }
    .form-kicker { display:inline-flex; align-items:center; gap:8px; margin-bottom:16px; padding:6px 10px; border:1px solid #d5f0e4; border-radius:999px; color:#177053; background:#effbf5; font-size:10px; font-weight:800; letter-spacing:.03em; }
    .form-kicker img { width:18px; height:18px; display:block; padding:2px; object-fit:contain; border-radius:50%; background:#d9f8ea; }
    .simple-form h2 { margin:0 0 7px; color:#11192c; font-size:31px; letter-spacing:-.055em; }
    .simple-form .intro { margin:0 0 27px; color:#718197; font-size:13px; }
    .oauth-button { width:100%; height:44px; display:flex; justify-content:center; align-items:center; gap:10px; margin-bottom:10px; border:1px solid #dce3eb; border-radius:10px; color:#25344a; background:#fff; box-shadow:0 4px 12px #174b3810; cursor:pointer; font-size:12px; transition:.2s ease; }
    .oauth-button:hover { border-color:#9bdcc4; background:#fbfffd; transform:translateY(-1px); box-shadow:0 7px 16px #174b3818; }
    .google-icon { width:15px; height:15px; display:grid; place-items:center; color:#4285f4; font-weight:900; }
    .form-divider { display:flex; align-items:center; gap:12px; margin:25px 0 30px; color:#9aa6b6; font-size:12px; }
    .form-divider:before,.form-divider:after { content:""; height:1px; flex:1; background:#e8edf2; }
    .simple-field { margin:0 0 16px; }
    .simple-field label { display:block; margin-bottom:7px; color:#26354a; font-size:11px; }
    .simple-input { width:100%; height:43px; padding:0 13px; border:1px solid #dce4ed; border-radius:10px; color:#1c2b40; background:#fff; box-shadow:0 4px 12px #174b3808; outline:none; font-size:12px; transition:.2s ease; }
    .simple-input:focus { border-color:#22ae78; box-shadow:0 0 0 3px #dff8ed; }
    .password-input-wrap { position:relative; }
    .password-input-wrap .simple-input { padding-right:42px; }
    .password-toggle { position:absolute; top:50%; right:10px; display:grid; place-items:center; width:26px; height:26px; padding:0; border:0; border-radius:7px; color:#708596; background:transparent; cursor:pointer; transform:translateY(-50%); }
    .password-toggle:hover { color:#17684f; background:#edf9f3; }
    .password-toggle svg { position:absolute; width:16px; height:16px; fill:none; stroke:currentColor; stroke-width:1.8; stroke-linecap:round; stroke-linejoin:round; transition:opacity .18s ease, transform .18s ease; }
    .password-toggle .eye-open { opacity:0; transform:scale(.72) rotate(-10deg); }
    .password-toggle .eye-closed { opacity:1; transform:scale(1) rotate(0); }
    .password-toggle.is-visible .eye-open { opacity:1; transform:scale(1) rotate(0); }
    .password-toggle.is-visible .eye-closed { opacity:0; transform:scale(.72) rotate(10deg); }
    .password-row { display:flex; align-items:center; justify-content:space-between; }
    .password-row a { color:#10a96d; font-size:11px; }
    .remember-row { display:flex; justify-content:space-between; align-items:center; margin:18px 0; color:#627187; font-size:11px; }
    .remember-row label { display:flex; align-items:center; gap:6px; }
    .simple-submit { width:100%; height:43px; border:0; border-radius:10px; color:#fff; background:linear-gradient(100deg,#17684f,#0b9b68); box-shadow:0 9px 18px #17684f2b; cursor:pointer; font-size:12px; font-weight:800; transition:.2s ease; }
    .simple-submit:hover { transform:translateY(-1px); box-shadow:0 12px 22px #17684f3b; }
    .simple-create { margin-top:24px; text-align:center; color:#718197; font-size:11px; }
    .simple-create a { display:block; margin-top:18px; color:#09a669; font-weight:800; }
    .form-benefits { display:flex; justify-content:center; gap:8px; flex-wrap:wrap; margin-top:25px; }
    .form-benefits span { padding:6px 9px; border-radius:999px; color:#52746d; background:#f0faf5; font-size:9px; }
    .simple-error { margin-bottom:14px; padding:10px 12px; border-radius:8px; color:#a32626; background:#fff0f0; font-size:12px; }
    @media (max-width:800px) { .simple-login { padding:0; align-items:stretch; } .simple-login-card { min-height:100vh; grid-template-columns:1fr; border:0; border-radius:0; } .simple-brand-panel { min-height:auto; padding:28px 24px 24px; } .simple-copy { margin-top:45px; } .simple-copy h1 { font-size:clamp(38px,9vw,48px); } .simple-visual { width:100%; } .simple-form-panel { min-height:560px; padding:40px 24px; } }
</style>

<main class="simple-login">
    <div class="simple-login-card">
    <section class="simple-brand-panel">
        <div class="brand-header"><a class="simple-brand" href="{{ route('home') }}"><img class="simple-brand-logo" src="{{ asset('images/logo1.png') }}" alt="Acid Reflux Tracker logo"><span class="brand-copy"><strong>Acid Reflux Tracker</strong><small>PERSONAL HEALTH WORKSPACE</small></span></a><div class="brand-kicker"><span class="brand-kicker-dot"></span> PRIVATE ACID-SAFE JOURNEY</div></div>
        <div class="simple-copy">
            <h1><span class="soft">Understand your</span><br>body,<br><span class="green">manage your health.</span></h1>
            <p>Track your food, symptoms, sleep, and health patterns to discover what triggers your acid reflux and take control of your well-being.</p>
            <div class="simple-visual"><div class="visual-top"><img class="visual-logo" src="{{ asset('images/logo1.png') }}" alt=""><span class="visual-line">Your health timeline</span></div><div class="visual-panel"><span>🍴</span><div><strong>Food &amp; symptom timeline</strong><em>Log patterns as they happen</em></div></div><div class="visual-panel"><span>☾</span><div><strong>Sleep &amp; stress tracking</strong><em>See what affects your day</em></div></div><div class="visual-large"><div><strong>Weekly pattern insight</strong><span>Personalized summaries from your health logs</span></div><span class="visual-score">✓</span></div></div>
        </div>
        <div class="simple-legal"><a href="#">Privacy Policy</a><a href="#">Terms of Service</a></div>
    </section>

    <section class="simple-form-panel">
        <div class="simple-form">
            <div class="form-kicker"><img src="{{ asset('images/logo1.png') }}" alt=""> YOUR PRIVATE HEALTH SPACE</div>
            <h2>Welcome back</h2><p class="intro">Please enter your details to sign in and continue tracking your health.</p>
            @if($errors->any()) <div class="simple-error">{{ $errors->first() }}</div> @endif
            <a class="oauth-button" href="{{ route('google.redirect') }}"><span class="google-icon">G</span>Continue with Google</a>
            <div class="form-divider">Or</div>
            <form method="POST" action="{{ route('login.submit') }}">
                @csrf
                <div class="simple-field"><label for="email">Email Address</label><input class="simple-input" id="email" name="email" type="email" value="{{ old('email') }}" placeholder="you@example.com" required autofocus></div>
                <div class="simple-field"><div class="password-row"><label for="password">Password</label><a href="#">Forgot your password?</a></div><div class="password-input-wrap"><input class="simple-input" id="password" name="password" type="password" placeholder="••••••••" required><button class="password-toggle" id="toggle-password" type="button" aria-label="Show password" title="Show password" aria-pressed="false"><svg class="eye-closed" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3l18 18"></path><path d="M10.6 6.2A10.7 10.7 0 0 1 12 6c6.5 0 10 6 10 6a18.8 18.8 0 0 1-3.1 3.6"></path><path d="M6.1 6.1C3.5 7.8 2 12 2 12s3.5 6 10 6a10.5 10.5 0 0 0 4.1-.8"></path></svg><svg class="eye-open" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"></path><circle cx="12" cy="12" r="2.5"></circle></svg></button></div></div>
                <div class="remember-row"><label><input type="checkbox" name="remember"> Remember me</label><span>Secure patient access</span></div>
                <button class="simple-submit" type="submit">Sign in</button>
            </form>
            <div class="simple-create">Don't have an account yet?<a href="{{ route('register') }}">Create an account</a></div>
            <div class="form-benefits"><span>✦ Private logs</span><span>◌ Pattern insights</span><span>♡ Personal care</span></div>
        </div>
    </section>
    </div>
</main>
<script>
    document.getElementById('toggle-password')?.addEventListener('click', function () {
        const password = document.getElementById('password');
        const showing = password.type === 'text';
        password.type = showing ? 'password' : 'text';
        this.classList.toggle('is-visible', !showing);
        this.setAttribute('aria-pressed', String(!showing));
        this.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
        this.setAttribute('title', showing ? 'Show password' : 'Hide password');
    });
</script>
@endsection
