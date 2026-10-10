@extends('legal.layout', ['title' => 'Terms of Service'])

@section('legal-kicker', 'The rules of the road')
@section('legal-title', 'Terms of Service')
@section('legal-intro', 'These terms explain what RefluxCare is, what you can expect from us, and what we expect from you. By creating an account or using RefluxCare, you agree to them.')
@section('legal-updated', 'October 10, 2026')

@section('legal-glance')
    <div><span><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8v.01"/></svg></span><strong>A tracking aid</strong><small>RefluxCare helps you spot patterns. It does not diagnose or treat.</small></div>
    <div><span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 4 6v6c0 5 3.4 8.4 8 9 4.6-.6 8-4 8-9V6l-8-3Z"/></svg></span><strong>Your data stays yours</strong><small>You own what you log and can export or delete it.</small></div>
    <div><span><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg></span><strong>Keep your account safe</strong><small>You are responsible for activity under your sign-in.</small></div>
    <div><span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg></span><strong>Use it fairly</strong><small>No misuse, scraping or attempts to access others' data.</small></div>
@endsection

@section('legal-body')
    <section class="lgl-section" id="about">
        <h2>About RefluxCare</h2>
        <p>RefluxCare is a personal health workspace for people managing acid reflux. You can log meals, symptoms, sleep and stress, and see patterns such as foods that are often followed by symptoms, a risk score, and personalized recommendations.</p>
    </section>

    <section class="lgl-section" id="not-medical-advice">
        <h2>Not a medical service</h2>
        <div class="lgl-note is-warning"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 9v4M12 17h.01"/><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/></svg><span>RefluxCare is not a medical device and does not provide medical advice, diagnosis or treatment. Insights, risk scores and recommendations are based only on what you log, and they can be incomplete or wrong.</span></div>
        <ul>
            <li>Talk to a qualified healthcare professional before changing your diet, medication or treatment.</li>
            <li>Never delay or ignore professional advice because of something you saw in RefluxCare.</li>
            <li>In an emergency, such as chest pain, trouble breathing or swallowing, or vomiting blood, contact emergency services immediately.</li>
        </ul>
    </section>

    <section class="lgl-section" id="your-account">
        <h2>Your account</h2>
        <ul>
            <li>You must give accurate information when you register, and keep your email address up to date.</li>
            <li>RefluxCare is intended for adults. Anyone under 18 should only use it with a parent or guardian.</li>
            <li>Keep your password private and sign out on shared devices. You are responsible for activity under your account.</li>
            <li>Tell an administrator straight away if you think someone else has accessed your account.</li>
        </ul>
    </section>

    <section class="lgl-section" id="acceptable-use">
        <h2>Acceptable use</h2>
        <p>When using RefluxCare, including its API, you agree not to:</p>
        <ul>
            <li>try to access another person's account or data, or get around security limits such as sign-in rate limiting;</li>
            <li>upload content that is unlawful, abusive, or not your own information;</li>
            <li>overload, disrupt, scan or reverse-engineer the service, or use automated tools to scrape it;</li>
            <li>use RefluxCare to give medical advice to others or present its insights as a diagnosis.</li>
        </ul>
    </section>

    <section class="lgl-section" id="your-content">
        <h2>Your content</h2>
        <p>You own the food, symptom, sleep and stress information you enter. You give RefluxCare permission to store and process it only to provide the service to you: showing your history, generating your insights, and keeping the service secure. Our <a href="{{ route('privacy') }}">Privacy Policy</a> explains this in detail.</p>
        <p>You can correct or delete individual entries at any time, and export your full history from <em>Health History</em>.</p>
    </section>

    <section class="lgl-section" id="availability">
        <h2>Availability and changes</h2>
        <p>We work to keep RefluxCare available and accurate, but we cannot promise it will be uninterrupted or error-free. We may improve, change or remove features. Keeping your own export of important records is a good idea.</p>
    </section>

    <section class="lgl-section" id="suspension">
        <h2>Suspension and closing your account</h2>
        <p>Administrators may deactivate an account that breaks these terms, puts other users at risk or is used fraudulently. A deactivated account is signed out and can no longer access the app or API.</p>
        <p>You may stop using RefluxCare at any time. To have your account and its logs deleted, contact an administrator.</p>
    </section>

    <section class="lgl-section" id="liability">
        <h2>Limitation of liability</h2>
        <p>RefluxCare is provided "as is". To the extent the law allows, we are not liable for decisions made based on its insights, or for indirect losses arising from using or being unable to use the service. Nothing in these terms limits rights you have under consumer protection law that cannot be waived.</p>
    </section>

    <section class="lgl-section" id="changes">
        <h2>Changes and contact</h2>
        <p>We may update these terms. When we do, we will change the date at the top of this page. If you keep using RefluxCare after an update, the updated terms apply.</p>
        <p>Questions about these terms? @if (config('app.contact_email'))Email <a href="mailto:{{ config('app.contact_email') }}">{{ config('app.contact_email') }}</a>.@else Contact your RefluxCare administrator.@endif</p>
    </section>
@endsection
