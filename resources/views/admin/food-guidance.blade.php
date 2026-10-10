@extends('admin.layout')

@section('admin-content')
@include('admin.partials.ui')
@php
    $lists = [
        'safe' => ['title' => 'Usually gentle', 'subtitle' => 'Often part of a calmer meal plan', 'foods' => $safeFoods, 'tone' => 'green', 'other' => 'avoid', 'otherLabel' => 'Common triggers', 'icon' => '<path d="M20 6 9 17l-5-5"/>'],
        'avoid' => ['title' => 'Common triggers', 'subtitle' => 'Foods patients are told to watch', 'foods' => $avoidFoods, 'tone' => 'red', 'other' => 'safe', 'otherLabel' => 'Usually gentle', 'icon' => '<path d="M12 9v4M12 17h.01"/><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/>'],
    ];
    $verdict = function (string $type, ?array $evidence): array {
        if (! $evidence) {
            return ['No patient data yet', 'slate', null];
        }
        if ($type === 'avoid') {
            return $evidence['triggerPatients'] > 0
                ? ['Confirmed by patient data', 'red', $evidence['triggerPatients'].' patient'.($evidence['triggerPatients'] === 1 ? '' : 's').' · '.$evidence['associations'].' symptom'.($evidence['associations'] === 1 ? '' : 's').' after it']
                : ['Not seen as a trigger yet', 'slate', 'Eaten '.$evidence['safeMeals'].'× with no symptoms'];
        }

        return $evidence['triggerPatients'] > 0
            ? ['Mixed results', 'amber', $evidence['triggerPatients'].' patient'.($evidence['triggerPatients'] === 1 ? '' : 's').' had symptoms after it']
            : ['Confirmed by patient data', 'green', 'Eaten '.$evidence['safeMeals'].'× with no symptoms'];
    };
    $backed = $safeFoods->merge($avoidFoods)->filter(fn (array $item): bool => $verdict($item['model']->type, $item['evidence'])[0] === 'Confirmed by patient data')->count();
    $suggestionCount = $suggestedTriggers->count() + $suggestedGentle->count();
    $moveIcon = '<path d="M7 7h11l-3-3M17 17H6l3 3"/>';
@endphp
<style>
    .fg-preview { width:min(380px,100%); padding:16px; border:1px solid #ffffff1f; border-radius:18px; background:#ffffff0f; backdrop-filter:blur(10px); }
    .fg-preview-top { display:flex; align-items:center; justify-content:space-between; gap:8px; margin-bottom:10px; color:#a7f3d0; font-size:11px; font-weight:700; letter-spacing:.08em; text-transform:uppercase; }
    .fg-preview-top span:last-child { padding:2px 8px; border-radius:999px; color:#062b20; background:#6ee7b7; letter-spacing:0; text-transform:none; }
    .fg-preview-card { padding:12px; border-radius:13px; color:var(--ink); background:#fff; box-shadow:0 10px 24px -12px #000000aa; }
    .fg-preview-card + .fg-preview-card { margin-top:8px; }
    .fg-preview-card strong { display:flex; align-items:center; gap:6px; font-size:12.5px; }
    .fg-preview-card strong i { width:8px; height:8px; border-radius:50%; background:var(--tone); }
    .fg-preview-chips { display:flex; flex-wrap:wrap; gap:4px; margin-top:8px; }
    .fg-preview-chips span { padding:2px 8px; border-radius:999px; color:var(--tone); background:var(--tone-soft); font-size:11px; font-weight:600; }
    .fg-stats { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:12px; }
    .fg-stat { display:flex; align-items:center; gap:12px; padding:14px; border:1px solid #fff; border-radius:18px; background:#ffffffd9; box-shadow:var(--shadow-soft); }
    .fg-stat .lp-badge-icon { width:42px; height:42px; border-radius:13px; }
    .fg-stat strong { display:block; font-size:24px; font-weight:800; line-height:1.1; letter-spacing:-.03em; }
    .fg-stat div > span { display:block; color:#334155; font-size:13px; font-weight:700; }
    .fg-stat small { display:block; color:#94a3b8; font-size:11.5px; }
    .fg-add { display:grid; grid-template-columns:minmax(0,1fr) auto auto; gap:10px; align-items:center; }
    .fg-type { display:flex; gap:4px; padding:4px; border:1px solid var(--line); border-radius:12px; background:#f8fafa; }
    .fg-type label { position:relative; display:inline-flex; align-items:center; gap:6px; height:34px; padding:0 12px; border-radius:9px; color:#475569; font-size:12.5px; font-weight:700; cursor:pointer; }
    .fg-type input { position:absolute; opacity:0; pointer-events:none; }
    .fg-type label i { width:8px; height:8px; border-radius:50%; background:var(--tone); }
    .fg-type label:has(input:checked) { color:#fff; background:var(--tone); }
    .fg-type label:has(input:checked) i { background:#fff; }
    .fg-type label:has(input:focus-visible) { outline:2px solid #10b981; }
    .fg-main { display:grid; grid-template-columns:minmax(0,1fr) minmax(0,1fr) 320px; gap:20px; align-items:start; }
    .fg-list-head { display:flex; align-items:flex-start; justify-content:space-between; gap:10px; margin-bottom:14px; }
    .fg-list-head h2 { display:flex; align-items:center; gap:8px; margin:0; font-size:17px; }
    .fg-list-head h2 i { width:10px; height:10px; border-radius:3px; background:var(--tone); }
    .fg-list-head p { margin:3px 0 0; color:var(--muted); font-size:12.5px; }
    .fg-list { display:grid; gap:8px; }
    .fg-item { position:relative; display:grid; grid-template-columns:38px minmax(0,1fr) auto; align-items:center; gap:12px; padding:11px 12px; border:1px solid #f1f5f4; border-radius:14px; background:#fafcfb; transition:border-color .2s ease,box-shadow .2s ease; }
    .fg-item:hover { border-color:var(--tone-mid); box-shadow:0 10px 22px -16px #0f172a59; }
    .fg-item .lp-badge-icon { width:38px; height:38px; border-radius:11px; }
    .fg-item-name { display:block; overflow:hidden; color:var(--ink); font-size:14px; font-weight:700; text-overflow:ellipsis; white-space:nowrap; }
    .fg-evidence { display:flex; flex-wrap:wrap; align-items:center; gap:6px; margin-top:3px; font-size:11.5px; }
    .fg-evidence b { display:inline-flex; align-items:center; gap:4px; padding:1px 7px; border-radius:999px; color:var(--tone); background:var(--tone-soft); font-weight:700; }
    .fg-evidence span { color:#94a3b8; }
    .fg-tools { display:flex; gap:4px; opacity:.55; transition:opacity .2s ease; }
    .fg-item:hover .fg-tools, .fg-item:focus-within .fg-tools { opacity:1; }
    @media (hover:none) { .fg-tools { opacity:1; } }
    .fg-tools form { margin:0; }
    .fg-tool { width:32px; height:32px; display:grid; place-items:center; padding:0; border:1px solid var(--line); border-radius:9px; color:#64748b; background:#fff; cursor:pointer; transition:color .15s ease,border-color .15s ease,background .15s ease; }
    .fg-tool:hover { color:var(--green); border-color:#a7f3d0; background:#ecfdf5; }
    .fg-tool.is-danger:hover { color:#b91c1c; border-color:#fecaca; background:#fef2f2; }
    .fg-tool svg { width:15px; height:15px; fill:none; stroke:currentColor; stroke-width:2; stroke-linecap:round; stroke-linejoin:round; }
    .fg-edit { display:none; grid-column:1 / -1; grid-template-columns:minmax(0,1fr) auto auto; gap:8px; align-items:center; }
    .fg-item.is-editing .fg-edit { display:grid; }
    .fg-item.is-editing > :not(.fg-edit) { display:none; }
    .fg-confirm { position:absolute; inset:0; z-index:2; display:none; align-items:center; justify-content:space-between; gap:8px; padding:8px 12px; border:1px solid #fecaca; border-radius:14px; background:#fff5f5f5; }
    .fg-item.is-confirming .fg-confirm { display:flex; animation:lpRise .2s ease both; }
    .fg-confirm span { color:#991b1b; font-size:12.5px; font-weight:600; }
    .fg-confirm div { display:flex; gap:6px; }
    .fg-none { display:none; padding:14px; color:var(--muted); font-size:12.5px; text-align:center; }
    .fg-side { display:grid; gap:16px; position:sticky; top:92px; }
    .fg-suggest { display:grid; gap:8px; }
    .fg-suggest-row { display:grid; grid-template-columns:minmax(0,1fr) auto; align-items:center; gap:10px; padding:10px 12px; border:1px solid #f1f5f4; border-radius:13px; background:#fafcfb; }
    .fg-suggest-row strong { display:block; font-size:13.5px; }
    .fg-suggest-row small { display:block; color:var(--muted); font-size:11.5px; }
    .fg-suggest-row form { margin:0; }
    .fg-sub { margin:14px 0 8px; color:#94a3b8; font-size:11px; font-weight:700; letter-spacing:.1em; text-transform:uppercase; }
    .fg-sub:first-of-type { margin-top:0; }
    .fg-note { display:flex; gap:8px; padding:10px 12px; border-radius:12px; color:#475569; background:#f8fafa; font-size:12px; line-height:1.5; }
    .fg-note svg { width:15px; height:15px; flex:none; margin-top:1px; fill:none; stroke:#059669; stroke-width:2; stroke-linecap:round; stroke-linejoin:round; }
    @media (max-width:1400px) { .fg-main { grid-template-columns:minmax(0,1fr) minmax(0,1fr); } .fg-side { grid-column:1 / -1; position:static; grid-template-columns:1fr 1fr; } }
    @media (max-width:1100px) { .fg-stats { grid-template-columns:1fr 1fr; } }
    @media (max-width:900px) { .fg-main, .fg-side { grid-template-columns:minmax(0,1fr); } }
    @media (max-width:640px) { .fg-add, .fg-edit { grid-template-columns:1fr; } .fg-stats { grid-template-columns:1fr; } }
</style>

<div class="lp">
    @if (session('status'))
        <div class="au-flash" role="status"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/></svg>{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="au-flash is-error" role="alert"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16.5v.01"/></svg>{{ $errors->first() }}</div>
    @endif

    <section class="lp-hero">
        <div>
            <span class="lp-crumb"><a href="{{ route('admin.dashboard') }}">Admin</a> / Food guidance</span>
            <h1>Food guidance</h1>
            <p>The general food lists every patient sees on their Recommendations page, next to their own results. Each food shows whether anonymised patient data backs it up.</p>
        </div>
        <div class="fg-preview" aria-label="What patients see">
            <div class="fg-preview-top"><span>Patient preview</span><span>Live</span></div>
            @foreach ($lists as $type => $list)
                <div class="fg-preview-card lp-tone-{{ $list['tone'] }}">
                    <strong><i></i>{{ $list['title'] }}</strong>
                    <div class="fg-preview-chips">
                        @forelse ($list['foods']->take(5) as $item)<span>{{ $item['model']->name }}</span>@empty<span>Nothing listed</span>@endforelse
                        @if ($list['foods']->count() > 5)<span>+{{ $list['foods']->count() - 5 }} more</span>@endif
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <section class="fg-stats" aria-label="Guidance summary">
        <div class="fg-stat lp-tone-green"><span class="lp-badge-icon"><svg class="lp-svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg></span><div><strong>{{ $safeFoods->count() }}</strong><span>Usually gentle</span><small>reflux-friendly foods</small></div></div>
        <div class="fg-stat lp-tone-red"><span class="lp-badge-icon"><svg class="lp-svg" viewBox="0 0 24 24" aria-hidden="true">{!! $lists['avoid']['icon'] !!}</svg></span><div><strong>{{ $avoidFoods->count() }}</strong><span>Common triggers</span><small>foods to watch</small></div></div>
        <div class="fg-stat lp-tone-sky"><span class="lp-badge-icon"><svg class="lp-svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3v18h18"/><path d="m7 15 4-4 3 3 5-6"/></svg></span><div><strong>{{ $backed }}</strong><span>Backed by data</span><small>from {{ $patientsWithData }} patient{{ $patientsWithData === 1 ? '' : 's' }} · {{ \App\Http\Controllers\AdminFoodCategoryController::EVIDENCE_DAYS }} days</small></div></div>
        <div class="fg-stat lp-tone-violet"><span class="lp-badge-icon"><svg class="lp-svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2l1.9 5.6L19.5 9.5l-5.6 1.9L12 17l-1.9-5.6L4.5 9.5l5.6-1.9z"/></svg></span><div><strong>{{ $suggestionCount }}</strong><span>Suggestions</span><small>foods worth adding</small></div></div>
    </section>

    <section class="lp-panel">
        <div class="lp-panel-head"><div><h2>Add a food</h2><p>Use short, general names patients will recognise, such as "Citrus" or "Oatmeal"</p></div></div>
        <form class="fg-add" method="POST" action="{{ route('admin.food-guidance.store') }}">
            @csrf
            <input class="au-input" name="name" value="{{ old('name') }}" maxlength="100" placeholder="Food name" aria-label="Food name" required>
            <div class="fg-type" role="radiogroup" aria-label="List">
                <label class="lp-tone-green"><input type="radio" name="type" value="safe" @checked(old('type', 'safe') === 'safe')><i></i> Usually gentle</label>
                <label class="lp-tone-red"><input type="radio" name="type" value="avoid" @checked(old('type') === 'avoid')><i></i> Common trigger</label>
            </div>
            <button class="au-btn is-primary" type="submit"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg> Add food</button>
        </form>
    </section>

    <div class="fg-main">
        @foreach ($lists as $type => $list)
            <section class="lp-panel lp-tone-{{ $list['tone'] }}">
                <div class="fg-list-head">
                    <div><h2><i></i>{{ $list['title'] }}</h2><p>{{ $list['subtitle'] }}</p></div>
                    <span class="au-pill lp-tone-{{ $list['tone'] }}">{{ $list['foods']->count() }} foods</span>
                </div>
                @if ($loop->first)
                    <label class="au-search" style="display:block;margin-bottom:12px"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg><input class="au-input" type="search" id="fg-filter" placeholder="Filter both lists…" aria-label="Filter both lists"></label>
                @else
                    <div style="height:54px" class="fg-filter-spacer" aria-hidden="true"></div>
                @endif
                <div class="fg-list">
                    @forelse ($list['foods'] as $item)
                        @php($food = $item['model'])
                        @php([$verdictLabel, $verdictTone, $verdictDetail] = $verdict($type, $item['evidence']))
                        <div class="fg-item lp-tone-{{ $list['tone'] }}" data-food-item data-name="{{ mb_strtolower($food->name) }}">
                            <span class="lp-badge-icon"><svg class="lp-svg" style="width:16px;height:16px" viewBox="0 0 24 24" aria-hidden="true">{!! $list['icon'] !!}</svg></span>
                            <div style="min-width:0">
                                <span class="fg-item-name">{{ $food->name }}</span>
                                <div class="fg-evidence lp-tone-{{ $verdictTone }}"><b>{{ $verdictLabel }}</b>@if ($verdictDetail)<span>{{ $verdictDetail }}</span>@endif</div>
                            </div>
                            <div class="fg-tools">
                                <button class="fg-tool" type="button" data-edit-food aria-label="Edit {{ $food->name }}" title="Edit"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg></button>
                                <form method="POST" action="{{ route('admin.food-guidance.update', $food) }}">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="name" value="{{ $food->name }}">
                                    <input type="hidden" name="type" value="{{ $list['other'] }}">
                                    <button class="fg-tool" type="submit" aria-label="Move {{ $food->name }} to {{ $list['otherLabel'] }}" title="Move to {{ $list['otherLabel'] }}"><svg viewBox="0 0 24 24" aria-hidden="true">{!! $moveIcon !!}</svg></button>
                                </form>
                                <button class="fg-tool is-danger" type="button" data-remove-food aria-label="Remove {{ $food->name }}" title="Remove"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6"/></svg></button>
                            </div>
                            <form class="fg-edit" method="POST" action="{{ route('admin.food-guidance.update', $food) }}">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="type" value="{{ $type }}">
                                <input class="au-input" name="name" value="{{ $food->name }}" maxlength="100" aria-label="Food name" required>
                                <button class="au-btn is-sm is-primary" type="submit">Save</button>
                                <button class="au-btn is-sm" type="button" data-cancel-edit>Cancel</button>
                            </form>
                            <form class="fg-confirm" method="POST" action="{{ route('admin.food-guidance.destroy', $food) }}">
                                @csrf
                                @method('DELETE')
                                <span>Remove {{ $food->name }}? Patients will stop seeing it.</span>
                                <div><button class="au-btn is-sm" type="button" data-cancel-remove>Keep</button><button class="au-btn is-sm is-solid-danger" type="submit">Remove</button></div>
                            </form>
                        </div>
                    @empty
                        <div class="lp-empty"><strong>No foods yet</strong>Add one above, or use a suggestion.</div>
                    @endforelse
                    <p class="fg-none" data-no-match>No foods match your filter.</p>
                </div>
            </section>
        @endforeach

        <aside class="fg-side">
            <section class="lp-panel">
                <div class="lp-panel-head" style="margin-bottom:14px"><div><h2>Suggestions</h2><p>From anonymised patient data · last {{ \App\Http\Controllers\AdminFoodCategoryController::EVIDENCE_DAYS }} days</p></div></div>
                <p class="fg-sub">Possible common triggers</p>
                <div class="fg-suggest">
                    @forelse ($suggestedTriggers as $suggestion)
                        <div class="fg-suggest-row">
                            <div><strong>{{ $suggestion['name'] }}</strong><small>{{ $suggestion['triggerPatients'] }} patient{{ $suggestion['triggerPatients'] === 1 ? '' : 's' }} · {{ $suggestion['associations'] }} symptom{{ $suggestion['associations'] === 1 ? '' : 's' }} after it</small></div>
                            <form method="POST" action="{{ route('admin.food-guidance.store') }}">@csrf<input type="hidden" name="name" value="{{ $suggestion['name'] }}"><input type="hidden" name="type" value="avoid"><button class="au-btn is-sm" type="submit">+ Trigger</button></form>
                        </div>
                    @empty
                        <p class="lp-hint" style="margin:0">Nothing yet. A food appears here once it is followed by symptoms at least twice.</p>
                    @endforelse
                </div>
                <p class="fg-sub">Possibly gentle</p>
                <div class="fg-suggest">
                    @forelse ($suggestedGentle as $suggestion)
                        <div class="fg-suggest-row">
                            <div><strong>{{ $suggestion['name'] }}</strong><small>Eaten {{ $suggestion['safeMeals'] }}× by {{ $suggestion['safePatients'] }} patient{{ $suggestion['safePatients'] === 1 ? '' : 's' }}, no symptoms</small></div>
                            <form method="POST" action="{{ route('admin.food-guidance.store') }}">@csrf<input type="hidden" name="name" value="{{ $suggestion['name'] }}"><input type="hidden" name="type" value="safe"><button class="au-btn is-sm" type="submit">+ Gentle</button></form>
                        </div>
                    @empty
                        <p class="lp-hint" style="margin:0">Nothing yet. A food appears here once it is eaten 3+ times with no symptoms.</p>
                    @endforelse
                </div>
            </section>
            <div class="fg-note"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg><span>Evidence is aggregated across all patients and never shows who ate what. It is a prompt to review the guidance, not medical proof.</span></div>
        </aside>
    </div>
</div>

<script>
    (() => {
        const items = [...document.querySelectorAll('[data-food-item]')];
        items.forEach((item) => {
            item.querySelector('[data-edit-food]').addEventListener('click', () => { item.classList.add('is-editing'); item.querySelector('.fg-edit input[name="name"]').focus(); });
            item.querySelector('[data-cancel-edit]').addEventListener('click', () => item.classList.remove('is-editing'));
            item.querySelector('[data-remove-food]').addEventListener('click', () => { item.classList.add('is-confirming'); item.querySelector('[data-cancel-remove]').focus(); });
            item.querySelector('[data-cancel-remove]').addEventListener('click', () => item.classList.remove('is-confirming'));
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') { items.forEach((item) => item.classList.remove('is-editing', 'is-confirming')); }
        });

        const filter = document.getElementById('fg-filter');
        filter?.addEventListener('input', () => {
            const term = filter.value.trim().toLowerCase();
            document.querySelectorAll('.fg-list').forEach((list) => {
                let visible = 0;
                list.querySelectorAll('[data-food-item]').forEach((item) => {
                    const match = item.dataset.name.includes(term);
                    item.hidden = ! match;
                    visible += match ? 1 : 0;
                });
                const none = list.querySelector('[data-no-match]');
                if (none) { none.style.display = visible === 0 && list.querySelectorAll('[data-food-item]').length ? 'block' : 'none'; }
            });
        });
    })();
</script>
@endsection
