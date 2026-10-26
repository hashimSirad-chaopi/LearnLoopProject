{{-- "Why are you taking this down?" dialog for Admin > Listings. Opened by any button with data-lm-open:
       single listing: data-lm-url, data-lm-method (PATCH = disable, DELETE = delete), data-lm-mode, data-lm-title
       several:        data-lm-bulk="disable|delete" (uses the ticked boxes and the #bulk-listings form)
     Usage: @include('partials.listing-moderation-dialog') once per page. --}}
<dialog class="lm-dialog" id="lm-dialog" aria-labelledby="lm-title">
    <form method="POST" action="#" class="lm-card" id="lm-form">
        @csrf
        <h2 id="lm-title">Disable this listing?</h2>
        <p class="lm-help" id="lm-help"></p>

        <label for="lm-reason" class="lm-label">Reason</label>
        <select name="reason" id="lm-reason" class="lm-field" required>
            <option value="" disabled selected>Choose why…</option>
            @foreach(\App\Support\ListingModeration::REASONS as $key => $label)
                <option value="{{ $key }}">{{ $label }}</option>
            @endforeach
        </select>

        <label for="lm-note" class="lm-label" id="lm-note-label">Add details (optional)</label>
        <textarea name="note" id="lm-note" class="lm-field" rows="3" maxlength="500" placeholder="The member will see this reason."></textarea>

        <div class="lm-actions">
            <button type="button" class="lm-btn lm-cancel" data-lm-cancel>Cancel</button>
            <button type="submit" class="lm-btn lm-confirm" id="lm-confirm">Disable</button>
        </div>
    </form>
</dialog>

<style>
    .lm-dialog {
        width: min(420px, calc(100vw - 32px)); margin: auto; padding: 0; max-height: calc(100vh - 32px); overflow-y: auto;
        border: 1px solid var(--ll-border-dark, #AEBBAF); border-radius: 16px;
        background: var(--ll-surface, #FBFDF9); color: var(--ll-text, #18251D);
        box-shadow: 0 18px 42px rgba(22, 35, 28, .28);
    }
    .lm-dialog::backdrop { background: rgba(22, 35, 28, .55); }
    .lm-card { display: flex; flex-direction: column; gap: 12px; padding: 24px; }
    .lm-card h2 { margin: 0; color: var(--ll-forest, #16231C); font-size: 18px; font-weight: 850; overflow-wrap: anywhere; }
    .lm-help { margin: 0; color: var(--ll-muted, #69766E); font-size: 12.5px; line-height: 1.5; overflow-wrap: anywhere; }
    .lm-label { color: var(--ll-forest, #16231C); font-size: 12px; font-weight: 850; letter-spacing: .3px; text-transform: uppercase; }
    .lm-field {
        width: 100%; padding: 10px 12px; font: inherit; font-size: 13px;
        border: 1px solid var(--ll-border-dark, #AEBBAF); border-radius: 10px;
        background: var(--ll-surface, #FBFDF9); color: var(--ll-text, #18251D);
    }
    textarea.lm-field { resize: vertical; }
    .lm-field:focus { outline: 2px solid var(--ll-forest, #16231C); outline-offset: 1px; }
    .lm-actions { display: flex; gap: 10px; margin-top: 4px; }
    .lm-btn { flex: 1; height: 42px; border-radius: 10px; font: inherit; font-size: 13px; font-weight: 800; cursor: pointer; }
    .lm-cancel { border: 1px solid var(--ll-border-dark, #AEBBAF); background: var(--ll-surface, #FBFDF9); color: var(--ll-forest, #16231C); }
    .lm-confirm { border: 1px solid var(--ll-danger, #B14D48); background: var(--ll-danger, #B14D48); color: #fff; }
    .lm-confirm:hover { background: #983E39; }
    .lm-btn:focus-visible { outline: 2px solid var(--ll-forest, #16231C); outline-offset: 2px; }
</style>

<script>
    (function () {
        var dialog = document.getElementById('lm-dialog');
        if (!dialog) return;
        var form = document.getElementById('lm-form');
        var title = document.getElementById('lm-title');
        var help = document.getElementById('lm-help');
        var confirmBtn = document.getElementById('lm-confirm');
        var reason = document.getElementById('lm-reason');
        var note = document.getElementById('lm-note');
        var noteLabel = document.getElementById('lm-note-label');
        var current = null; // what the open dialog will do

        function syncNote() {
            var isOther = reason.value === 'other';
            note.required = isOther;
            noteLabel.textContent = isOther ? 'Type the reason (required)' : 'Add details (optional)';
            note.placeholder = isOther ? 'Explain why this is being taken down.' : 'The member will see this reason.';
        }
        reason.addEventListener('change', syncNote);

        function hidden(name, value) {
            var input = document.createElement('input');
            input.type = 'hidden'; input.name = name; input.value = value;
            return input;
        }

        // One delegated listener, so the buttons keep working after a live update replaces the list.
        document.addEventListener('click', function (event) {
            var button = event.target.closest('[data-lm-open]');
            if (!button) return;

            var bulkMode = button.getAttribute('data-lm-bulk');
            var mode = bulkMode || button.getAttribute('data-lm-mode');
            var name = button.getAttribute('data-lm-title') || 'this listing';

            if (bulkMode && !document.querySelectorAll('#bulk-listings .bulk-check:checked, .bulk-check[form="bulk-listings"]:checked').length) {
                alert('Tick at least one listing first.');
                return;
            }

            current = { bulk: bulkMode, url: button.getAttribute('data-lm-url'), method: button.getAttribute('data-lm-method') };
            var verb = mode === 'delete' ? 'Delete' : 'Disable';
            title.textContent = verb + ' ' + (bulkMode ? name : '“' + name + '”') + '?';
            help.textContent = mode === 'delete'
                ? 'It is removed for good, along with its comments and likes. The member gets a notification with your reason.'
                : 'It disappears from the feed but stays in the member’s My Listings with your reason. You can restore it any time.';
            confirmBtn.textContent = verb;
            form.reset();
            syncNote();
            dialog.showModal();
        });

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            if (!current) return;

            var target;
            if (current.bulk) {
                target = document.getElementById('bulk-listings');
                target.querySelectorAll('[data-lm-added]').forEach(function (el) { el.remove(); });
                [hidden('bulk', current.bulk), hidden('reason', reason.value), hidden('note', note.value)].forEach(function (el) {
                    el.setAttribute('data-lm-added', '1');
                    target.appendChild(el);
                });
            } else {
                target = document.createElement('form');
                target.method = 'POST';
                target.action = current.url;
                target.style.display = 'none';
                target.appendChild(hidden('_token', form.querySelector('input[name="_token"]').value));
                target.appendChild(hidden('_method', current.method));
                target.appendChild(hidden('reason', reason.value));
                target.appendChild(hidden('note', note.value));
                document.body.appendChild(target);
            }
            dialog.close();
            target.submit();
        });

        dialog.querySelector('[data-lm-cancel]').addEventListener('click', function () { dialog.close(); });
        dialog.addEventListener('click', function (e) { if (e.target === dialog) dialog.close(); });
    })();
</script>
