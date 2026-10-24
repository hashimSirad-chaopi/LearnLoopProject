{{-- "Suspend for how long?" dialog, opened by any button with data-suspend-open.
     The button supplies data-suspend-url (the suspend route) and data-suspend-name.
     Usage: @include('partials.suspend-dialog') once per page. --}}
<dialog class="sd-dialog" id="suspend-dialog" aria-labelledby="sd-title">
    <form method="POST" action="#" class="sd-card" id="suspend-form">
        @csrf @method('PATCH')
        <h2 id="sd-title">Suspend <span id="sd-name">this member</span>?</h2>
        <p class="sd-help">They are signed out of the app straight away and told by notification. You can reactivate the account at any time, even before the time is up.</p>

        <span class="sd-label">How long</span>
        <div class="sd-options" role="radiogroup" aria-label="How long to suspend">
            @foreach(\App\Support\Suspension::OPTIONS as $key => $label)
                <label class="sd-option">
                    <input type="radio" name="duration" value="{{ $key }}" @checked($key === '1w')>
                    <span>{{ $label }}</span>
                </label>
            @endforeach
        </div>

        <label for="sd-reason" class="sd-label">Reason</label>
        <select name="reason" id="sd-reason" class="sd-field" required>
            <option value="" disabled selected>Choose why this account is being suspended…</option>
            @foreach(\App\Support\Suspension::REASONS as $key => $label)
                <option value="{{ $key }}">{{ $label }}</option>
            @endforeach
        </select>

        <label for="sd-note" class="sd-label" id="sd-note-label">Add details (optional)</label>
        <textarea name="note" id="sd-note" class="sd-field" rows="3" maxlength="500" placeholder="The member will see this reason."></textarea>

        <div class="sd-actions">
            <button type="button" class="sd-btn sd-cancel" data-sd-cancel>Cancel</button>
            <button type="submit" class="sd-btn sd-confirm">Suspend</button>
        </div>
    </form>
</dialog>

<style>
    .sd-dialog {
        width: min(400px, calc(100vw - 32px)); margin: auto; padding: 0;
        border: 1px solid var(--ll-border-dark, #AEBBAF); border-radius: 16px;
        background: var(--ll-surface, #FBFDF9); color: var(--ll-text, #18251D);
        box-shadow: 0 18px 42px rgba(22, 35, 28, .28);
    }
    .sd-dialog::backdrop { background: rgba(22, 35, 28, .55); }
    .sd-dialog { max-height: calc(100vh - 32px); overflow-y: auto; }
    .sd-card { display: flex; flex-direction: column; gap: 12px; padding: 24px; }
    .sd-label { color: var(--ll-forest, #16231C); font-size: 12px; font-weight: 850; letter-spacing: .3px; text-transform: uppercase; }
    .sd-field {
        width: 100%; padding: 10px 12px; font: inherit; font-size: 13px;
        border: 1px solid var(--ll-border-dark, #AEBBAF); border-radius: 10px;
        background: var(--ll-surface, #FBFDF9); color: var(--ll-text, #18251D);
    }
    textarea.sd-field { resize: vertical; }
    .sd-field:focus { outline: 2px solid var(--ll-forest, #16231C); outline-offset: 1px; }
    .sd-card h2 { margin: 0; color: var(--ll-forest, #16231C); font-size: 18px; font-weight: 850; overflow-wrap: anywhere; }
    .sd-help { margin: 0; color: var(--ll-muted, #69766E); font-size: 12.5px; line-height: 1.5; }
    .sd-options { display: flex; flex-direction: column; gap: 8px; }
    .sd-option {
        display: flex; align-items: center; gap: 10px; padding: 11px 12px; cursor: pointer;
        border: 1px solid var(--ll-border, #D5DDD6); border-radius: 12px; font-size: 13.5px; font-weight: 700;
    }
    .sd-option:has(input:checked) { border-color: var(--ll-forest, #16231C); background: var(--ll-mint-soft, #E7F6D9); }
    .sd-option input { accent-color: var(--ll-forest, #16231C); }
    .sd-actions { display: flex; gap: 10px; margin-top: 4px; }
    .sd-btn { flex: 1; height: 42px; border-radius: 10px; font: inherit; font-size: 13px; font-weight: 800; cursor: pointer; }
    .sd-cancel { border: 1px solid var(--ll-border-dark, #AEBBAF); background: var(--ll-surface, #FBFDF9); color: var(--ll-forest, #16231C); }
    .sd-confirm { border: 1px solid var(--ll-danger, #B14D48); background: var(--ll-danger, #B14D48); color: #fff; }
    .sd-confirm:hover { background: #983E39; }
    .sd-btn:focus-visible { outline: 2px solid var(--ll-forest, #16231C); outline-offset: 2px; }
</style>

<script>
    (function () {
        var dialog = document.getElementById('suspend-dialog');
        if (!dialog) return;
        var form = document.getElementById('suspend-form');
        var name = document.getElementById('sd-name');

        var reason = document.getElementById('sd-reason');
        var note = document.getElementById('sd-note');
        var noteLabel = document.getElementById('sd-note-label');

        // "Other" has to be explained in words; the other reasons take an optional note.
        function syncNote() {
            var isOther = reason.value === 'other';
            note.required = isOther;
            noteLabel.textContent = isOther ? 'Type the reason (required)' : 'Add details (optional)';
            note.placeholder = isOther ? 'Explain why this account is being suspended.' : 'The member will see this reason.';
        }
        reason.addEventListener('change', syncNote);

        // One delegated listener, so the buttons keep working after a live update replaces the table.
        document.addEventListener('click', function (event) {
            var button = event.target.closest('[data-suspend-open]');
            if (!button) return;

            form.reset();
            syncNote();
            form.action = button.getAttribute('data-suspend-url');
            name.textContent = button.getAttribute('data-suspend-name') || 'this member';
            if (typeof dialog.showModal === 'function') {
                dialog.showModal();
            } else if (confirm('Suspend ' + name.textContent + ' (totally)?')) {
                form.querySelector('input[value="forever"]').checked = true;
                reason.value = 'rules';
                form.submit();
            }
        });

        dialog.querySelector('[data-sd-cancel]').addEventListener('click', function () { dialog.close(); });
        dialog.addEventListener('click', function (e) { if (e.target === dialog) dialog.close(); });
    })();
</script>
