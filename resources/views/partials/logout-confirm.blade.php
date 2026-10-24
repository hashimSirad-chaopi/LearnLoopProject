{{-- Log Out button + confirmation dialog. Used by both admin sidebars.
     Usage: @include('partials.logout-confirm') --}}
<button type="button" class="logout-btn" data-logout-open>Log Out</button>

<dialog class="lo-dialog" id="logout-dialog" aria-labelledby="lo-title" aria-describedby="lo-text">
    <form method="POST" action="{{ route('logout') }}" class="lo-card">
        @csrf
        <div class="lo-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/>
            </svg>
        </div>
        <h2 id="lo-title">Log out?</h2>
        <p id="lo-text">You will need to sign in again to reach the admin panel.</p>
        <div class="lo-actions">
            <button type="button" class="lo-btn lo-cancel" data-logout-cancel autofocus>Cancel</button>
            <button type="submit" class="lo-btn lo-confirm">Log out</button>
        </div>
    </form>
</dialog>

<style>
    .lo-dialog {
        width: min(380px, calc(100vw - 32px));
        margin: auto; /* the global * { margin: 0 } reset would pin it to the top-left */
        padding: 0;
        border: 1px solid var(--ll-border-dark, #AEBBAF);
        border-radius: 16px;
        background: var(--ll-surface, #FBFDF9);
        color: var(--ll-text, #18251D);
        box-shadow: 0 18px 42px rgba(22, 35, 28, .28);
    }
    .lo-dialog::backdrop { background: rgba(22, 35, 28, .55); }
    .lo-card { display: flex; flex-direction: column; align-items: center; gap: 8px; padding: 26px 24px 22px; text-align: center; }
    .lo-icon {
        display: flex; align-items: center; justify-content: center;
        width: 52px; height: 52px; margin-bottom: 4px; border-radius: 50%;
        background: var(--ll-danger-soft, #F9E9E7); color: var(--ll-danger, #B14D48);
    }
    .lo-card h2 { margin: 0; color: var(--ll-forest, #16231C); font-size: 18px; font-weight: 850; }
    .lo-card p { margin: 0 0 10px; color: var(--ll-muted, #69766E); font-size: 13px; line-height: 1.5; }
    .lo-actions { display: flex; gap: 10px; width: 100%; }
    .lo-btn {
        flex: 1; height: 42px; border-radius: 10px; font: inherit; font-size: 13px; font-weight: 800; cursor: pointer;
        transition: background .15s ease, border-color .15s ease;
    }
    .lo-cancel { border: 1px solid var(--ll-border-dark, #AEBBAF); background: var(--ll-surface, #FBFDF9); color: var(--ll-forest, #16231C); }
    .lo-cancel:hover { background: #F3F6F3; border-color: var(--ll-forest, #16231C); }
    .lo-confirm { border: 1px solid var(--ll-danger, #B14D48); background: var(--ll-danger, #B14D48); color: #fff; }
    .lo-confirm:hover { background: #983E39; border-color: #983E39; }
    .lo-btn:focus-visible { outline: 2px solid var(--ll-forest, #16231C); outline-offset: 2px; }
</style>

<script>
    (function () {
        var dialog = document.getElementById('logout-dialog');
        if (!dialog) return;
        var open = document.querySelector('[data-logout-open]');
        var cancel = dialog.querySelector('[data-logout-cancel]');

        open.addEventListener('click', function () {
            document.body.classList.remove('sidebar-open'); // close the mobile menu behind the dialog
            if (typeof dialog.showModal === 'function') {
                dialog.showModal();
            } else if (confirm('Log out?')) {   // very old browsers without <dialog>
                dialog.querySelector('form').submit();
            }
        });
        cancel.addEventListener('click', function () { dialog.close(); });

        // click on the dimmed area outside the card closes it too
        dialog.addEventListener('click', function (e) { if (e.target === dialog) dialog.close(); });
    })();
</script>
