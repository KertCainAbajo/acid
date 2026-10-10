@extends('legal.layout', ['title' => 'Privacy Policy'])

@section('legal-kicker', 'Your data, your control')
@section('legal-title', 'Privacy Policy')
@section('legal-intro', 'RefluxCare handles sensitive health information. This policy explains exactly what we collect, why, who can see it, and the controls you have over it.')
@section('legal-updated', 'October 10, 2026')

@section('legal-glance')
    <div><span><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg></span><strong>Private by default</strong><small>Your health logs are only visible to your own account.</small></div>
    <div><span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 4 6v6c0 5 3.4 8.4 8 9 4.6-.6 8-4 8-9V6l-8-3Z"/><path d="m9 12 2 2 4-4"/></svg></span><strong>Never sold</strong><small>We do not sell your data or use it for advertising.</small></div>
    <div><span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3v18h18"/><path d="m7 15 4-4 3 3 5-6"/></svg></span><strong>Analysed on our server</strong><small>Pattern insights are calculated by RefluxCare, not sent to outside AI services.</small></div>
    <div><span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12M7 10l5 5 5-5"/><path d="M4 21h16"/></svg></span><strong>Yours to export</strong><small>Edit, delete or download your records at any time.</small></div>
@endsection

@section('legal-body')
    <section class="lgl-section" id="information-we-collect">
        <h2>Information we collect</h2>
        <p>We only collect what RefluxCare needs to work. Most of it is information you choose to enter.</p>
        <table class="lgl-table">
            <thead><tr><th>Type</th><th>What it includes</th></tr></thead>
            <tbody>
                <tr><td>Account details</td><td>Your name, email address and password. Passwords are stored as a secure one-way hash, so nobody (including us) can read them.</td></tr>
                <tr><td>Google sign-in</td><td>If you choose Google, we receive your name and verified email address from Google. We never receive your Google password.</td></tr>
                <tr><td>Food logs</td><td>What you ate or drank, its category, the time, and any notes you add.</td></tr>
                <tr><td>Symptom logs</td><td>The symptom type, its intensity (1–10), the time, and any notes you add.</td></tr>
                <tr><td>Sleep &amp; stress logs</td><td>Hours slept, sleep quality, stress level, the date, and any notes you add.</td></tr>
                <tr><td>Generated insights</td><td>Your risk level and score, the factors behind it, and the pattern summaries RefluxCare creates from your logs.</td></tr>
                <tr><td>Activity &amp; security records</td><td>Sign-ins, sign-outs, account creation and changes to your entries, with the time and the IP address used. These protect your account and help investigate misuse.</td></tr>
            </tbody>
        </table>
        <div class="lgl-note is-warning"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 9v4M12 17h.01"/><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/></svg><span>Notes are free text. Avoid writing information you would not want stored, such as other people's details or identification numbers.</span></div>
    </section>

    <section class="lgl-section" id="how-we-use-it">
        <h2>How we use your information</h2>
        <ul>
            <li><strong>To run your workspace:</strong> showing your dashboard, timelines, history and exports.</li>
            <li><strong>To find your patterns:</strong> comparing meals with symptoms that follow within 6 hours, and looking at timing, sleep and stress, to produce your risk score and recommendations. This analysis runs on the RefluxCare server. Your logs are not sent to third-party AI or analytics services.</li>
            <li><strong>To keep your account secure:</strong> limiting repeated sign-in attempts, expiring access tokens, and keeping activity records.</li>
            <li><strong>To operate the service:</strong> producing overall statistics for administrators, such as how many entries exist or how risk levels are distributed, which do not identify you.</li>
        </ul>
        <p>We do not use your health information for advertising, and we do not make automated decisions about you that have legal or similar effects. Insights are a tracking aid only.</p>
    </section>

    <section class="lgl-section" id="who-can-see-it">
        <h2>Who can see your information</h2>
        <h3>You</h3>
        <p>You can see and manage all of your own entries, insights and history. Other patients can never see your data. Every request is checked against your account.</p>
        <h3>Administrators</h3>
        <p>RefluxCare administrators can see account details (name, email, account status and sign-up date), activity records, and aggregated statistics. They use this to support users, keep the service secure and deactivate accounts that misuse it.</p>
        <h3>Service providers</h3>
        <p>Google is involved only if you choose to sign in with Google. Our hosting provider stores the data on our behalf. We do not sell, rent or trade your information.</p>
        <h3>Legal requirements</h3>
        <p>We may disclose information if required by law, or to protect the safety of a person, where the law allows it.</p>
    </section>

    <section class="lgl-section" id="security">
        <h2>How we protect it</h2>
        <ul>
            <li>Passwords are hashed and never stored in readable form.</li>
            <li>Sign-in attempts are rate limited to stop password guessing.</li>
            <li>The access tokens used by the web app expire automatically and are revoked when you sign out.</li>
            <li>Deactivated accounts are signed out immediately and lose API access.</li>
            <li>Every entry is tied to its owner, and changes to entries are recorded in the activity log.</li>
        </ul>
        <p>No system is perfectly secure. Use a strong, unique password and sign out on shared devices.</p>
    </section>

    <section class="lgl-section" id="cookies">
        <h2>Cookies</h2>
        <p>RefluxCare uses only the cookies it needs to work. There are no advertising or third-party tracking cookies.</p>
        <table class="lgl-table">
            <thead><tr><th>Cookie</th><th>Purpose</th></tr></thead>
            <tbody>
                <tr><td>Session</td><td>Keeps you signed in while you use the app. It ends when the session expires or you sign out.</td></tr>
                <tr><td>Security (XSRF)</td><td>Protects forms against cross-site request forgery.</td></tr>
                <tr><td>Remember me</td><td>Only set if you tick "Remember me" or use Google sign-in, so you stay signed in on that device.</td></tr>
            </tbody>
        </table>
    </section>

    <section class="lgl-section" id="your-choices">
        <h2>Your rights and choices</h2>
        <ul>
            <li><strong>Access and portability:</strong> download your full history as a CSV file from <em>Health History</em>.</li>
            <li><strong>Correction:</strong> edit any food, symptom or sleep entry from its page.</li>
            <li><strong>Deletion:</strong> delete individual entries at any time. To delete your whole account, contact an administrator.</li>
            <li><strong>Stopping:</strong> you can stop logging at any time. Insights are only built from what you choose to record.</li>
        </ul>
        <p>Depending on where you live, data protection laws, such as the Data Privacy Act of 2012 in the Philippines, may give you further rights. Contact us to use them.</p>
    </section>

    <section class="lgl-section" id="retention">
        <h2>How long we keep it</h2>
        <p>We keep your entries and insights while your account exists. Entries you delete are removed straight away. When an account is deleted, its food, symptom and sleep logs and its insights are deleted with it. Activity records are kept for security purposes, but they are no longer linked to the deleted account.</p>
    </section>

    <section class="lgl-section" id="children">
        <h2>Children</h2>
        <p>RefluxCare is intended for adults. Anyone under 18 should only use it with the involvement of a parent or guardian.</p>
    </section>

    <section class="lgl-section" id="medical">
        <h2>Not medical advice</h2>
        <div class="lgl-note"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8v.01"/></svg><span>RefluxCare is a tracking aid. Its insights are patterns in your own logs, not a diagnosis. Always speak to a healthcare professional about your symptoms. Seek urgent care for chest pain, trouble swallowing, vomiting blood or pain spreading to your arm or jaw.</span></div>
    </section>

    <section class="lgl-section" id="changes">
        <h2>Changes and contact</h2>
        <p>If we change this policy, we will update the date at the top of this page. Significant changes will be highlighted in the app.</p>
        <p>For questions or requests about your data, @if (config('app.contact_email'))email <a href="mailto:{{ config('app.contact_email') }}">{{ config('app.contact_email') }}</a>.@else contact your RefluxCare administrator.@endif See also our <a href="{{ route('terms') }}">Terms of Service</a>.</p>
    </section>
@endsection
