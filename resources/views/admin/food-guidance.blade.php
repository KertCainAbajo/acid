@extends('admin.layout')

@section('admin-content')
@include('admin.partials.ui')
<style>
    .fg-add { display:grid; grid-template-columns:minmax(0,1fr) auto auto; gap:10px; align-items:start; }
    .fg-type { display:flex; gap:4px; padding:4px; border:1px solid var(--line); border-radius:12px; background:#f8fafa; }
    .fg-type label { position:relative; display:inline-flex; align-items:center; gap:6px; height:32px; padding:0 12px; border-radius:9px; color:#475569; font-size:12.5px; font-weight:700; cursor:pointer; }
    .fg-type input { position:absolute; opacity:0; pointer-events:none; }
    .fg-type label:has(input:checked) { color:#fff; background:var(--tone); }
    .fg-type label:has(input:focus-visible) { outline:2px solid #10b981; }
    .fg-columns { display:grid; grid-template-columns:1fr 1fr; gap:20px; align-items:start; }
    .fg-list { display:grid; gap:8px; }
    .fg-item { display:grid; grid-template-columns:36px minmax(0,1fr) auto; align-items:center; gap:12px; padding:10px 12px; border:1px solid #f1f5f4; border-radius:14px; background:#fafcfb; transition:border-color .2s ease; }
    .fg-item:hover { border-color:var(--tone-mid); }
    .fg-item .lp-badge-icon { width:36px; height:36px; border-radius:11px; }
    .fg-item strong { font-size:14px; }
    .fg-edit { display:none; grid-column:1 / -1; grid-template-columns:minmax(0,1fr) auto auto auto; gap:8px; align-items:center; }
    .fg-item.is-editing .fg-edit { display:grid; }
    .fg-item.is-editing .fg-view { display:none; }
    .fg-view { display:contents; }
    @media (max-width:1000px) { .fg-columns { grid-template-columns:1fr; } }
    @media (max-width:640px) { .fg-add, .fg-edit { grid-template-columns:1fr; } }
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
            <p>These lists appear as general guidance on every patient's Recommendations page, next to each patient's own results.</p>
        </div>
        <div class="lp-hero-stats">
            <div class="lp-hero-stat"><span>Usually gentle</span><strong>{{ $safeFoods->count() }}</strong><small>reflux-friendly foods</small></div>
            <div class="lp-hero-stat"><span>Common triggers</span><strong>{{ $avoidFoods->count() }}</strong><small>foods to watch</small></div>
        </div>
    </section>

    <section class="lp-panel">
        <div class="lp-panel-head"><div><h2>Add a food</h2><p>Use short, general names patients will recognise, such as "Citrus" or "Oatmeal"</p></div></div>
        <form class="fg-add" method="POST" action="{{ route('admin.food-guidance.store') }}">
            @csrf
            <input class="au-input" name="name" value="{{ old('name') }}" maxlength="100" placeholder="Food name" aria-label="Food name" required>
            <div class="fg-type" role="radiogroup" aria-label="List">
                <label class="lp-tone-green"><input type="radio" name="type" value="safe" @checked(old('type', 'safe') === 'safe')> Usually gentle</label>
                <label class="lp-tone-red"><input type="radio" name="type" value="avoid" @checked(old('type') === 'avoid')> Common trigger</label>
            </div>
            <button class="au-btn is-primary" type="submit"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg> Add food</button>
        </form>
    </section>

    <div class="fg-columns">
        @foreach ([['Usually gentle', 'Often part of a calmer meal plan', $safeFoods, 'green', 'safe', '<path d="M20 6 9 17l-5-5"/>'], ['Common triggers', 'Foods patients are told to watch', $avoidFoods, 'red', 'avoid', '<path d="M12 9v4M12 17h.01"/><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/>']] as [$heading, $subheading, $foods, $tone, $type, $icon])
            <section class="lp-panel">
                <div class="lp-panel-head"><div><h2>{{ $heading }}</h2><p>{{ $subheading }}</p></div><span class="au-pill lp-tone-{{ $tone }}">{{ $foods->count() }} foods</span></div>
                <div class="fg-list">
                    @forelse ($foods as $food)
                        <div class="fg-item lp-tone-{{ $tone }}" data-food-item>
                            <div class="fg-view">
                                <span class="lp-badge-icon"><svg class="lp-svg" style="width:16px;height:16px" viewBox="0 0 24 24" aria-hidden="true">{!! $icon !!}</svg></span>
                                <strong>{{ $food->name }}</strong>
                                <div class="au-row-actions">
                                    <button class="au-btn is-sm" type="button" data-edit-food>Edit</button>
                                    <form method="POST" action="{{ route('admin.food-guidance.destroy', $food) }}" onsubmit="return confirm(@js("Remove {$food->name} from {$heading}? Patients will no longer see it."))">
                                        @csrf
                                        @method('DELETE')
                                        <button class="au-btn is-sm is-danger" type="submit">Remove</button>
                                    </form>
                                </div>
                            </div>
                            <form class="fg-edit" method="POST" action="{{ route('admin.food-guidance.update', $food) }}">
                                @csrf
                                @method('PUT')
                                <input class="au-input" name="name" value="{{ $food->name }}" maxlength="100" aria-label="Food name" required>
                                <select class="au-select" name="type" aria-label="List">
                                    <option value="safe" @selected($type === 'safe')>Usually gentle</option>
                                    <option value="avoid" @selected($type === 'avoid')>Common trigger</option>
                                </select>
                                <button class="au-btn is-sm is-primary" type="submit">Save</button>
                                <button class="au-btn is-sm" type="button" data-cancel-edit>Cancel</button>
                            </form>
                        </div>
                    @empty
                        <div class="lp-empty"><strong>No foods yet</strong>Add one above.</div>
                    @endforelse
                </div>
            </section>
        @endforeach
    </div>
</div>

<script>
    document.querySelectorAll('[data-food-item]').forEach((item) => {
        item.querySelector('[data-edit-food]').addEventListener('click', () => {
            item.classList.add('is-editing');
            item.querySelector('.fg-edit input').focus();
        });
        item.querySelector('[data-cancel-edit]').addEventListener('click', () => item.classList.remove('is-editing'));
    });
</script>
@endsection
