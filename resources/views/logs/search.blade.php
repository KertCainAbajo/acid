@extends('layouts.app')

@section('content')
<style>
    .search-main { padding:0; background:radial-gradient(circle at 12% 8%,#dff9ed 0,transparent 34%),linear-gradient(135deg,#f3fcf8,#f8fbfc 60%,#fff); }
    .search-page { min-height:calc(100vh - 74px); max-width:1120px; margin:0 auto; padding:52px clamp(22px,4vw,58px) 70px; }
    .search-hero { padding:31px; border:1px solid #d2ebdf; border-radius:28px; background:linear-gradient(125deg,#fff,#effbf5 62%,#dff8ec); box-shadow:0 22px 55px #17684f12; }
    .search-kicker { display:flex; align-items:center; gap:8px; color:#137458; font-size:11px; font-weight:850; letter-spacing:.08em; text-transform:uppercase; }
    .search-kicker i { width:8px; height:8px; border-radius:50%; background:#35cc8d; box-shadow:0 0 0 5px #35cc8d1c; }
    .search-hero h1 { margin:11px 0 8px; font-size:clamp(31px,4vw,46px); }
    .search-hero p { margin:0; color:#668087; }
    .search-form { display:flex; gap:10px; margin-top:24px; }
    .search-form input { min-width:0; flex:1; padding:15px 18px; border:1px solid #cfe5dc; border-radius:16px; background:#fff; color:#173e47; font-size:15px; outline:none; }
    .search-form input:focus { border-color:#4dab8d; box-shadow:0 0 0 4px #dff6ec; }
    .search-form button { flex:none; border:0; border-radius:16px; padding:0 20px; color:#fff; background:linear-gradient(135deg,#17684f,#0e9b6b); font-weight:800; cursor:pointer; box-shadow:0 10px 20px #17684f24; }
    .search-results { margin-top:22px; padding:26px; border:1px solid #dcece7; border-radius:24px; background:#fff; box-shadow:0 16px 35px #174b3810; }
    .search-results-header { display:flex; align-items:center; justify-content:space-between; gap:14px; margin-bottom:15px; }
    .search-results h2 { margin:0; font-size:20px; }
    .search-count { padding:6px 10px; border-radius:999px; color:#17684f; background:#dff8ec; font-size:10px; font-weight:800; }
    .search-list { display:grid; gap:10px; }
    .search-result { display:flex; align-items:center; gap:13px; padding:14px; border:1px solid #e0efea; border-radius:17px; background:#fbfefd; }
    .search-result-icon { display:grid; place-items:center; flex:none; width:34px; height:34px; border-radius:11px; color:#17684f; background:#ddf8ee; font-size:15px; font-weight:850; }
    .search-result.symptom .search-result-icon { color:#a73b33; background:#ffe5e1; }
    .search-result.sleep .search-result-icon { color:#3565a8; background:#e3edff; }
    .search-result strong { display:block; color:#173e47; font-size:13px; }
    .search-result small { display:block; margin-top:3px; color:#75898d; font-size:11px; }
    .search-empty { padding:31px 18px; border:1px dashed #cde4da; border-radius:18px; color:#6b8386; background:#fbfffd; text-align:center; }
    @media (max-width:650px) { .search-page { padding:28px 15px 45px; } .search-hero, .search-results { padding:21px; border-radius:21px; } .search-form { flex-direction:column; } .search-form button { min-height:50px; } }
</style>

<div class="shell">
    @include('partials.topbar')

    <div class="layout">
        @include('partials.sidebar', ['activeSection' => 'search'])

        <main class="main search-main">
            <div class="search-page">
                <section class="search-hero">
                    <div class="search-kicker"><i></i> Your private records</div>
                    <h1>Search your health logs</h1>
                    <p>Find a meal, symptom, category, or note from your own health history.</p>
                    <form class="search-form" action="{{ route('logs.search') }}" method="GET">
                        <input name="q" value="{{ $query }}" maxlength="100" autofocus placeholder="Try “oatmeal”, “heartburn”, or “spicy”" aria-label="Search your health logs">
                        <button type="submit">Search logs</button>
                    </form>
                </section>

                <section class="search-results" aria-live="polite">
                    <div class="search-results-header">
                        <h2>{{ $query === '' ? 'Ready when you are' : 'Search results' }}</h2>
                        @if($query !== '')<span class="search-count">{{ $results->count() }} found</span>@endif
                    </div>
                    @if($query === '')
                        <div class="search-empty">Enter a word above to search your own food, symptom, sleep, and stress records.</div>
                    @elseif($results->isEmpty())
                        <div class="search-empty">No records matched “{{ $query }}”. Try a food name, category, symptom, or note.</div>
                    @else
                        <div class="search-list">
                            @foreach($results as $result)
                                <article class="search-result {{ $result['tone'] }}"><span class="search-result-icon">{{ $result['tone'] === 'food' ? '⌑' : ($result['tone'] === 'symptom' ? '!' : '☾') }}</span><div><strong>{{ $result['kind'] }} · {{ $result['title'] }}</strong><small>{{ $result['detail'] }}</small></div></article>
                            @endforeach
                        </div>
                    @endif
                </section>
            </div>
        </main>
    </div>
</div>
@endsection
