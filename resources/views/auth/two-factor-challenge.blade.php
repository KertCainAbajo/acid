@extends('layouts.app')

@section('content')
@include('auth.partials.styles')
<style>
    .tf { position:relative; z-index:1; width:min(460px,100%); padding:clamp(24px,4vw,40px); border:1px solid #fff; border-radius:28px; background:#ffffffeb; box-shadow:0 1px 2px #0f172a0d,0 40px 100px -30px #0a5a4047; backdrop-filter:blur(24px); animation:lgEnter .5s cubic-bezier(.2,.8,.2,1) both; }
    .tf-icon { width:64px; height:64px; display:grid; place-items:center; margin-bottom:18px; border-radius:20px; color:#fff; background:linear-gradient(135deg,#12916a,#0a5a40); box-shadow:0 16px 30px -12px #0a5a40aa; }
    .tf-icon svg { width:30px; height:30px; fill:none; stroke:currentColor; stroke-width:2; stroke-linecap:round; stroke-linejoin:round; }
    .tf h1 { margin:0; color:var(--ink); font-size:26px; font-weight:800; letter-spacing:-.03em; }
    .tf p { margin:8px 0 22px; color:var(--muted); font-size:14px; line-height:1.6; }
    .tf-code { width:100%; height:64px; border:1.5px solid var(--line); border-radius:16px; color:var(--ink); background:#f8fafb; outline:none; font:800 28px ui-monospace,monospace; letter-spacing:.45em; text-align:center; text-indent:.45em; transition:border-color .2s ease,box-shadow .2s ease; }
    .tf-code:focus { border-color:#10b981; background:#fff; box-shadow:0 0 0 4px #d1fae5b3; }
    .tf-code[aria-invalid="true"] { border-color:#f87171; }
    .tf-recovery { display:none; }
    .tf.is-recovery .tf-recovery { display:block; }
    .tf.is-recovery .tf-totp { display:none; }
    .tf-switch { display:block; margin:16px auto 0; padding:0; border:0; color:var(--brand); background:none; font:inherit; font-size:13.5px; font-weight:700; cursor:pointer; }
    .tf-back { display:block; margin-top:14px; color:var(--muted); font-size:13px; text-align:center; }
    .tf-back a { color:var(--brand); font-weight:700; }
    .tf-error { margin:10px 0 0; color:#b91c1c; font-size:13px; font-weight:600; text-align:center; }
    .tf .lg-submit { margin-top:16px; }
</style>

<main class="lg">
    <div class="tf" id="two-factor-card">
        <div class="tf-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="2" width="14" height="20" rx="3"/><path d="M12 18h.01M9 7l2 2 4-4"/></svg></div>
        <h1>Two-factor verification</h1>
        <p class="tf-totp-text">Open your authenticator app and enter the 6-digit code for <strong>RefluxCare</strong>.</p>

        <form method="POST" action="{{ route('two-factor.verify') }}" id="two-factor-form">
            @csrf
            <div class="tf-totp">
                <input class="tf-code" name="code" id="code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" pattern="[0-9]{6}" placeholder="••••••" autofocus aria-label="6-digit code" @error('code') aria-invalid="true" @enderror>
            </div>
            <div class="tf-recovery">
                <input class="tf-code" style="font-size:20px;letter-spacing:.15em;text-indent:.15em" name="recovery_code" id="recovery_code" autocomplete="off" maxlength="20" placeholder="XXXXX-XXXXX" aria-label="Recovery code">
            </div>
            @error('code')<p class="tf-error" role="alert">{{ $message }}</p>@enderror
            <button class="lg-submit" type="submit"><span>Verify and sign in</span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></button>
        </form>

        <button class="tf-switch" type="button" id="toggle-recovery">Lost your phone? Use a recovery code</button>
        <p class="tf-back">Not you? <a href="{{ route('login') }}">Back to sign in</a></p>
    </div>
</main>

<script>
    (() => {
        const card = document.getElementById('two-factor-card');
        const toggle = document.getElementById('toggle-recovery');
        const code = document.getElementById('code');
        const recovery = document.getElementById('recovery_code');
        toggle.addEventListener('click', () => {
            const useRecovery = card.classList.toggle('is-recovery');
            toggle.textContent = useRecovery ? 'Use your authenticator app instead' : 'Lost your phone? Use a recovery code';
            (useRecovery ? code : recovery).value = '';
            (useRecovery ? recovery : code).focus();
        });
        code.addEventListener('input', () => {
            code.value = code.value.replace(/\D/g, '').slice(0, 6);
            if (code.value.length === 6) { document.getElementById('two-factor-form').requestSubmit(); }
        });
    })();
</script>
@endsection
