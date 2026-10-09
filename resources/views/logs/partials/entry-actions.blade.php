<style>
    .lp-entry-side { display:flex; flex-direction:column; align-items:flex-end; gap:6px; }
    .lp-entry-actions { display:flex; gap:4px; opacity:0; transform:translateY(-2px); transition:opacity .18s ease,transform .18s ease; }
    .lp-entry:hover .lp-entry-actions, .lp-entry:focus-within .lp-entry-actions, .lp-entry.is-editing .lp-entry-actions { opacity:1; transform:none; }
    @media (hover:none) { .lp-entry-actions { opacity:1; transform:none; } }
    .lp-icon-btn { width:30px; height:30px; display:grid; place-items:center; padding:0; border:1px solid var(--line); border-radius:9px; color:#64748b; background:#fff; cursor:pointer; transition:color .15s ease,border-color .15s ease,background .15s ease; }
    .lp-icon-btn svg { width:14px; height:14px; fill:none; stroke:currentColor; stroke-width:2; stroke-linecap:round; stroke-linejoin:round; }
    .lp-icon-btn:hover { color:var(--green); border-color:#a7f3d0; background:#ecfdf5; }
    .lp-icon-btn.is-danger:hover { color:#b91c1c; border-color:#fecaca; background:#fef2f2; }
    .lp-icon-btn:focus-visible { outline:2px solid #10b981; outline-offset:1px; }
    .lp-entry { position:relative; transition:background .2s ease,border-color .2s ease,opacity .3s ease,transform .3s ease; }
    .lp-entry.is-editing { border-color:#a7f3d0 !important; background:#f0fdf4 !important; }
    .lp-entry.is-removing { opacity:0; transform:translateX(12px); }
    .lp-confirm { position:absolute; inset:0; z-index:2; display:flex; align-items:center; justify-content:space-between; gap:10px; padding:10px 12px; border:1px solid #fecaca; border-radius:14px; background:#fff5f5f2; backdrop-filter:blur(4px); animation:lpRise .2s ease both; }
    .lp-confirm span { color:#991b1b; font-size:13px; font-weight:600; }
    .lp-confirm div { display:flex; gap:6px; }
    .lp-confirm button { padding:7px 12px; border:1px solid var(--line); border-radius:9px; color:#334155; background:#fff; font-size:12.5px; font-weight:700; cursor:pointer; }
    .lp-confirm button.is-danger { border-color:#dc2626; color:#fff; background:#dc2626; }
    .lp-confirm button:disabled { opacity:.6; cursor:wait; }
    .lp-editing-banner { display:flex; align-items:center; justify-content:space-between; gap:12px; margin:0 0 20px; padding:12px 14px; border:1px solid #a7f3d0; border-radius:14px; color:var(--green-deep); background:linear-gradient(120deg,#ecfdf5,#f0fdf4); font-size:13.5px; animation:lpRise .25s ease both; }
    .lp-editing-banner strong { font-weight:800; }
    .lp-editing-banner span { display:flex; align-items:center; gap:8px; min-width:0; }
    .lp-editing-banner svg { flex:none; width:16px; height:16px; fill:none; stroke:currentColor; stroke-width:2; stroke-linecap:round; stroke-linejoin:round; }
    .lp-editing-banner button { flex:none; padding:7px 12px; border:1px solid #a7f3d0; border-radius:9px; color:var(--green-deep); background:#fff; font-size:12.5px; font-weight:700; cursor:pointer; }
    .lp-editing-banner button:hover { background:#f0fdf4; }
</style>

<script>
    /**
     * Adds edit and delete actions to the history entries of a log page.
     *
     * Rows need the `lp-entry` class and a `data-entry` JSON payload with `id` and `label`.
     */
    window.createEntryActions = ({ form, endpoint, token, noun, submitLabel, createText, updateText, fill, reset, onStatus }) => {
        const pencil = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>';
        let banner = null;

        const headers = { 'Accept': 'application/json', 'Authorization': `Bearer ${token}` };
        const editingId = () => form.dataset.editingId || null;

        const clearEditing = () => {
            delete form.dataset.editingId;
            banner?.remove();
            banner = null;
            submitLabel.textContent = createText;
            document.querySelectorAll('.lp-entry.is-editing').forEach((row) => row.classList.remove('is-editing'));
        };

        const cancelEditing = () => {
            clearEditing();
            reset();
        };

        const startEditing = (row) => {
            const entry = JSON.parse(row.dataset.entry);
            clearEditing();
            form.dataset.editingId = entry.id;
            row.classList.add('is-editing');
            fill(entry);
            submitLabel.textContent = updateText;

            banner = document.createElement('div');
            banner.className = 'lp-editing-banner';
            banner.setAttribute('role', 'status');
            banner.innerHTML = `<span>${pencil}<span>Editing <strong></strong></span></span><button type="button">Cancel edit</button>`;
            banner.querySelector('strong').textContent = entry.label;
            banner.querySelector('button').addEventListener('click', cancelEditing);
            form.prepend(banner);

            form.scrollIntoView({ behavior: 'smooth', block: 'start' });
            form.querySelector('input:not([type="hidden"]):not([type="radio"]), textarea')?.focus({ preventScroll: true });
        };

        const confirmDelete = (row) => {
            if (row.querySelector('.lp-confirm')) { return; }
            const entry = JSON.parse(row.dataset.entry);
            const confirmBox = document.createElement('div');
            confirmBox.className = 'lp-confirm';
            confirmBox.setAttribute('role', 'alertdialog');
            confirmBox.innerHTML = `<span>Delete this ${noun}?</span><div><button type="button" data-cancel>Keep</button><button type="button" class="is-danger" data-confirm>Delete</button></div>`;
            row.appendChild(confirmBox);
            confirmBox.querySelector('[data-cancel]').focus();

            confirmBox.querySelector('[data-cancel]').addEventListener('click', () => confirmBox.remove());
            confirmBox.addEventListener('keydown', (event) => { if (event.key === 'Escape') { confirmBox.remove(); } });
            confirmBox.querySelector('[data-confirm]').addEventListener('click', async (event) => {
                event.target.disabled = true;
                event.target.textContent = 'Deleting…';
                try {
                    const response = await fetch(`${endpoint}/${entry.id}`, { method: 'DELETE', headers });
                    const payload = await response.json();
                    if (! response.ok) { throw new Error(payload.message || `This ${noun} could not be deleted.`); }
                    if (editingId() === String(entry.id)) { cancelEditing(); }
                    row.classList.add('is-removing');
                    onStatus('success', `“${entry.label}” was deleted. Updating your history…`);
                    setTimeout(() => window.location.reload(), 700);
                } catch (error) {
                    confirmBox.remove();
                    onStatus('error', error.message);
                }
            });
        };

        document.querySelectorAll('.lp-entry[data-entry]').forEach((row) => {
            row.querySelector('[data-edit]')?.addEventListener('click', () => startEditing(row));
            row.querySelector('[data-delete]')?.addEventListener('click', () => confirmDelete(row));
        });
        document.addEventListener('keydown', (event) => { if (event.key === 'Escape' && editingId() && ! document.querySelector('.lp-confirm')) { cancelEditing(); } });

        return {
            /** The request target for the form: create a new entry, or update the one being edited. */
            request: () => editingId() ? { url: `${endpoint}/${editingId()}`, method: 'PUT' } : { url: endpoint, method: 'POST' },
            isEditing: () => editingId() !== null,
        };
    };
</script>
