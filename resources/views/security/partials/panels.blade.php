@php
    $twoFactorErrors = $errors->getBag('twoFactor');
    $sessionErrors = $errors->getBag('sessions');
    $scoreTone = $score >= 75 ? 'green' : ($score >= 50 ? 'amber' : 'red');
@endphp
<style>
    .sc-grid { display:grid; grid-template-columns:minmax(0,1.3fr) minmax(0,1fr); gap:20px; align-items:start; }
    .sc-score { display:flex; align-items:center; gap:18px; }
    .sc-ring { position:relative; width:96px; height:96px; flex:none; }
    .sc-ring svg { width:96px; height:96px; transform:rotate(-90deg); }
    .sc-ring circle { fill:none; stroke-width:9; }
    .sc-ring .track { stroke:#eef2f1; } .sc-ring .value { stroke:var(--tone); stroke-linecap:round; stroke-dasharray:100; }
    .sc-ring b { position:absolute; inset:0; display:grid; place-items:center; color:var(--tone); font-size:22px; font-weight:800; }
    .sc-checks { display:grid; gap:8px; margin:0; padding:0; list-style:none; flex:1; }
    .sc-checks li { display:flex; align-items:center; gap:9px; color:#475569; font-size:13px; }
    .sc-checks li svg { width:18px; height:18px; flex:none; padding:3px; border-radius:50%; fill:none; stroke:#fff; stroke-width:3.2; stroke-linecap:round; stroke-linejoin:round; background:#cbd5e1; }
    .sc-checks li.is-done { color:#065f46; } .sc-checks li.is-done svg { background:#10b981; }
    .sc-layers { display:grid; grid-template-columns:repeat(5,minmax(0,1fr)); gap:8px; margin-top:16px; }
    .sc-layer { padding:10px; border:1px solid #eef2f1; border-radius:12px; background:#fafcfb; text-align:center; }
    .sc-layer b { display:block; color:var(--green); font-size:11px; letter-spacing:.06em; text-transform:uppercase; }
    .sc-layer span { display:block; margin-top:2px; color:#334155; font-size:12px; font-weight:600; line-height:1.3; }
    .sc-status { display:inline-flex; align-items:center; gap:6px; padding:4px 10px; border-radius:999px; color:var(--tone); background:var(--tone-soft); box-shadow:inset 0 0 0 1px var(--tone-mid); font-size:12px; font-weight:700; }
    .sc-status i { width:7px; height:7px; border-radius:50%; background:currentColor; }
    .sc-steps { display:grid; gap:12px; margin:14px 0; padding:0; list-style:none; counter-reset:step; }
    .sc-steps li { position:relative; padding-left:34px; color:#475569; font-size:13.5px; line-height:1.55; }
    .sc-steps li:before { counter-increment:step; content:counter(step); position:absolute; left:0; top:0; width:24px; height:24px; display:grid; place-items:center; border-radius:50%; color:#fff; background:var(--green); font-size:12px; font-weight:800; }
    .sc-key { display:flex; flex-wrap:wrap; align-items:center; gap:8px; margin-top:6px; }
    .sc-key code { padding:8px 12px; border:1px dashed #a7f3d0; border-radius:10px; color:#064e3b; background:#f0fdf4; font:700 15px ui-monospace,monospace; letter-spacing:.08em; }
    .sc-inline { display:flex; flex-wrap:wrap; align-items:flex-start; gap:8px; margin-top:12px; }
    .sc-inline .au-input { width:200px; }
    .sc-code-input { font:700 18px ui-monospace,monospace !important; letter-spacing:.3em; text-align:center; }
    .sc-codes { display:grid; grid-template-columns:repeat(2,1fr); gap:6px; margin:12px 0; padding:14px; border-radius:14px; background:#0b1f2a; }
    .sc-codes code { color:#d1fae5; font:600 14px ui-monospace,monospace; text-align:center; }
    .sc-warning { display:flex; gap:10px; margin-top:12px; padding:12px 14px; border-radius:13px; color:#92400e; background:#fffbeb; box-shadow:inset 0 0 0 1px #fde68a; font-size:13px; line-height:1.5; }
    .sc-warning svg { width:18px; height:18px; flex:none; fill:none; stroke:currentColor; stroke-width:2; stroke-linecap:round; stroke-linejoin:round; }
    .sc-details { margin-top:14px; border-top:1px solid #eef2f1; padding-top:12px; }
    .sc-details summary { color:#475569; font-size:13px; font-weight:700; cursor:pointer; }
    .sc-history { display:grid; gap:4px; }
    .sc-history-row { display:grid; grid-template-columns:34px minmax(0,1fr) auto; align-items:center; gap:10px; padding:8px; border-radius:11px; }
    .sc-history-row:hover { background:#fafcfb; }
    .sc-history-row .lp-badge-icon { width:34px; height:34px; border-radius:10px; }
    .sc-history-row strong { font-size:13px; }
    .sc-history-row small { display:block; color:var(--muted); font-size:11.5px; }
    .sc-history-row time { color:#94a3b8; font-size:11.5px; white-space:nowrap; }
    @media (max-width:1100px) { .sc-grid { grid-template-columns:minmax(0,1fr); } .sc-layers { grid-template-columns:repeat(2,minmax(0,1fr)); } }
    @media (max-width:560px) { .sc-score { flex-direction:column; align-items:flex-start; } }
</style>

<section class="lp-panel lp-tone-{{ $scoreTone }}" id="security-score">
    <div class="lp-panel-head"><div><h2>Your security score</h2><p>How well this account is protected right now</p></div></div>
    <div class="sc-score">
        <div class="sc-ring" role="img" aria-label="Security score {{ $score }} percent">
            <svg viewBox="0 0 96 96"><circle class="track" cx="48" cy="48" r="40" pathLength="100"/><circle class="value" cx="48" cy="48" r="40" pathLength="100" @style(['stroke-dashoffset: '.(100 - max(2, $score))])/></svg>
            <b>{{ $score }}%</b>
        </div>
        <ul class="sc-checks">
            @foreach ($checks as $check)
                <li class="{{ $check['done'] ? 'is-done' : '' }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 5 5 9-10"/></svg>{{ $check['label'] }}</li>
            @endforeach
        </ul>
    </div>
    <div class="sc-layers" aria-label="Security layers protecting this account">
        <div class="sc-layer"><b>Layer 1</b><span>Lockout after {{ \App\Services\LoginGuard::MAX_ATTEMPTS }} failed sign-ins</span></div>
        <div class="sc-layer"><b>Layer 2</b><span>Two-factor {{ $twoFactorEnabled ? 'on' : 'off' }}</span></div>
        <div class="sc-layer"><b>Layer 3</b><span>Signs out after {{ $user->idleTimeoutMinutes() }} min idle</span></div>
        <div class="sc-layer"><b>Layer 4</b><span>Hardened browser headers</span></div>
        <div class="sc-layer"><b>Layer 5</b><span>Every sign-in recorded</span></div>
    </div>
</section>

<div class="sc-grid">
    <section class="lp-panel lp-tone-{{ $twoFactorEnabled ? 'green' : 'amber' }}" id="two-factor">
        <div class="lp-panel-head">
            <div><h2>Two-factor authentication</h2><p>A 6-digit code from your phone, needed every time you sign in</p></div>
            <span class="sc-status"><i></i>{{ $twoFactorEnabled ? 'On' : ($twoFactorPending ? 'Setup in progress' : 'Off') }}</span>
        </div>

        @if ($newRecoveryCodes)
            <div class="sc-warning"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 9v4M12 17h.01"/><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/></svg><span><strong>Save these recovery codes now.</strong> Each one signs you in once if you lose your phone. They will not be shown again.</span></div>
            <div class="sc-codes" id="recovery-codes">@foreach ($newRecoveryCodes as $code)<code>{{ $code }}</code>@endforeach</div>
            <div class="sc-inline">
                <button class="au-btn is-sm" type="button" data-copy-codes>Copy codes</button>
                <button class="au-btn is-sm" type="button" data-download-codes>Download .txt</button>
            </div>
        @endif

        @if ($twoFactorEnabled)
            <p class="lp-hint" style="margin:0">Your account asks for a code from your authenticator app after your password. You have <strong>{{ $recoveryCodesLeft }}</strong> unused recovery code{{ $recoveryCodesLeft === 1 ? '' : 's' }}.</p>
            @if ($recoveryCodesLeft <= 2)
                <div class="sc-warning"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 9v4M12 17h.01"/><circle cx="12" cy="12" r="9"/></svg><span>You are running low on recovery codes. Create new ones below.</span></div>
            @endif
            <details class="sc-details" @if ($twoFactorErrors->any()) open @endif>
                <summary>Manage two-factor authentication</summary>
                <form class="sc-inline" method="POST" action="{{ route('security.two-factor.recovery') }}">
                    @csrf
                    <input class="au-input" type="password" name="current_password" placeholder="Your password" autocomplete="current-password" required aria-label="Your password">
                    <button class="au-btn" type="submit">Create new recovery codes</button>
                </form>
                @unless ($user->mustUseTwoFactor())
                    <form class="sc-inline" method="POST" action="{{ route('security.two-factor.disable') }}" data-confirm="Turn off two-factor authentication? Your account will only be protected by your password.">
                        @csrf
                        @method('DELETE')
                        <input class="au-input" type="password" name="current_password" placeholder="Your password" autocomplete="current-password" required aria-label="Your password">
                        <button class="au-btn is-danger" type="submit">Turn off two-factor</button>
                    </form>
                @else
                    <p class="lp-hint" style="margin:12px 0 0">Administrators must keep two-factor authentication on.</p>
                @endunless
                @if ($twoFactorErrors->any())<p class="au-error">{{ $twoFactorErrors->first() }}</p>@endif
            </details>
        @elseif ($twoFactorPending)
            <ol class="sc-steps">
                <li>Install an authenticator app such as Google Authenticator, Microsoft Authenticator or Authy.</li>
                <li>Add an account and choose <strong>Enter a setup key</strong>, then type this key (time-based):
                    <div class="sc-key"><code id="setup-key">{{ $setupKey }}</code><button class="au-btn is-sm" type="button" data-copy-key>Copy key</button><a class="au-btn is-sm" href="{{ $setupUri }}">Open in app</a></div>
                </li>
                <li>Enter the 6-digit code the app shows:
                    <form class="sc-inline" method="POST" action="{{ route('security.two-factor.confirm') }}">
                        @csrf
                        <input class="au-input sc-code-input" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" pattern="[0-9]{6}" placeholder="000000" required aria-label="6-digit code" @if ($twoFactorErrors->has('code')) aria-invalid="true" @endif>
                        <button class="au-btn is-primary" type="submit">Turn on two-factor</button>
                    </form>
                    @if ($twoFactorErrors->any())<p class="au-error">{{ $twoFactorErrors->first() }}</p>@endif
                </li>
            </ol>
        @else
            <p class="lp-hint" style="margin:0 0 12px">Even if someone learns your password, they cannot sign in without the code from your phone.{{ $user->mustUseTwoFactor() ? ' Administrators are required to use it.' : '' }}</p>
            <form method="POST" action="{{ route('security.two-factor.enable') }}">
                @csrf
                <button class="au-btn is-primary" type="submit"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 4 6v6c0 5 3.4 8.4 8 9 4.6-.6 8-4 8-9V6l-8-3Z"/><path d="m9 12 2 2 4-4"/></svg> Set up two-factor</button>
            </form>
        @endif
    </section>

    <section class="lp-panel" id="sessions">
        <div class="lp-panel-head"><div><h2>Signed-in devices</h2><p>Sessions that are signed in to this account</p></div><span class="sc-status lp-tone-{{ $otherSessions ? 'amber' : 'green' }}"><i></i>{{ $otherSessions + 1 }} active</span></div>
        <p class="lp-hint" style="margin:0">This browser plus <strong>{{ $otherSessions }}</strong> other session{{ $otherSessions === 1 ? '' : 's' }}. You are signed out automatically after {{ $user->idleTimeoutMinutes() }} minutes without activity.</p>
        <form class="sc-inline" method="POST" action="{{ route('security.sessions.destroy') }}">
            @csrf
            <input class="au-input" type="password" name="current_password" placeholder="Your password" autocomplete="current-password" required aria-label="Your password" @if ($sessionErrors->any()) aria-invalid="true" @endif>
            <button class="au-btn" type="submit">Sign out other devices</button>
        </form>
        @if ($sessionErrors->any())<p class="au-error">{{ $sessionErrors->first() }}</p>@endif
    </section>
</div>

<section class="lp-panel" id="sign-in-history">
    <div class="lp-panel-head"><div><h2>Sign-in history</h2><p>Recent sign-ins and security events for this account. If anything looks unfamiliar, change your password.</p></div></div>
    @forelse ($history as $event)
        @php(['label' => $label, 'tone' => $tone, 'icon' => $icon] = \App\Support\ActivityPresenter::describe($event->action))
        <div class="sc-history-row lp-tone-{{ $tone }}">
            <span class="lp-badge-icon"><svg class="lp-svg" style="width:15px;height:15px" viewBox="0 0 24 24" aria-hidden="true">{!! $icon !!}</svg></span>
            <div><strong>{{ $label }}</strong><small>{{ collect([$event->metadata['browser'] ?? null, isset($event->metadata['channel']) ? 'via '.$event->metadata['channel'] : null, $event->ip_address])->filter()->implode(' · ') ?: '—' }}</small></div>
            <time datetime="{{ $event->created_at->toIso8601String() }}" title="{{ $event->created_at->format('M j, Y g:i A') }}">{{ $event->created_at->diffForHumans() }}</time>
        </div>
    @empty
        <p class="lp-hint" style="margin:0">No sign-in activity recorded yet.</p>
    @endforelse
</section>

<script>
    (() => {
        document.querySelectorAll('form[data-confirm]').forEach((form) => form.addEventListener('submit', (event) => {
            if (! window.confirm(form.dataset.confirm)) { event.preventDefault(); }
        }));
        const codesText = () => [...document.querySelectorAll('#recovery-codes code')].map((code) => code.textContent).join('\n');
        document.querySelector('[data-copy-codes]')?.addEventListener('click', async (event) => {
            try { await navigator.clipboard.writeText(codesText()); event.target.textContent = 'Copied ✓'; } catch (error) { event.target.textContent = 'Copy failed'; }
        });
        document.querySelector('[data-download-codes]')?.addEventListener('click', () => {
            const link = document.createElement('a');
            link.href = URL.createObjectURL(new Blob([`RefluxCare recovery codes\nEach code works once.\n\n${codesText()}\n`], { type: 'text/plain' }));
            link.download = 'refluxcare-recovery-codes.txt';
            link.click();
            URL.revokeObjectURL(link.href);
        });
        document.querySelector('[data-copy-key]')?.addEventListener('click', async (event) => {
            try { await navigator.clipboard.writeText(document.getElementById('setup-key').textContent.replace(/\s/g, '')); event.target.textContent = 'Copied ✓'; } catch (error) { event.target.textContent = 'Copy failed'; }
        });
    })();
</script>
