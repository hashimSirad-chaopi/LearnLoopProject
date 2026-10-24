{{-- Live updates for the admin panel.
     Pages that opt in (<main class="main-content" data-live>) re-fetch themselves every few seconds and
     swap in whatever changed: member Active / Offline badges, stat cards, tables, notifications and the
     sidebar counts. It never swaps while you are busy (typing, a field focused, boxes ticked, a dialog
     open): a small "New updates" button appears instead. Included once, from the sidebar. --}}
<style>
    .live-pill {
        position: fixed; right: 14px; bottom: 14px; z-index: 40;
        display: inline-flex; align-items: center; gap: 6px; padding: 5px 11px;
        border: 1px solid var(--ll-border-dark, #AEBBAF); border-radius: 999px;
        background: var(--ll-surface, #FBFDF9); color: var(--ll-forest, #16231C);
        font-size: 11px; font-weight: 800; letter-spacing: .3px; pointer-events: none;
        box-shadow: 0 4px 14px rgba(22, 35, 28, .12);
    }
    .live-pill i { width: 8px; height: 8px; border-radius: 50%; background: #3FA34D; animation: live-pulse 1.8s ease-in-out infinite; }
    .live-pill.paused i { background: #A6B0A9; animation: none; }
    @keyframes live-pulse { 0%, 100% { opacity: 1; } 50% { opacity: .35; } }
    .live-chip {
        position: fixed; left: 50%; bottom: 18px; z-index: 41; transform: translateX(-50%);
        padding: 9px 16px; border: 0; border-radius: 999px; cursor: pointer;
        background: var(--ll-forest, #16231C); color: var(--ll-mint, #B7FF72);
        font: inherit; font-size: 12.5px; font-weight: 800; box-shadow: 0 8px 22px rgba(22, 35, 28, .3);
    }
    .live-chip[hidden] { display: none; }
    @media (prefers-reduced-motion: reduce) { .live-pill i { animation: none; } }
</style>

<script>
    (function () {
        var INTERVAL = 8000;           // how often a page checks for changes (ms)
        var main = null, nav = null;
        var baseline = null;           // what the server last said the page looks like
        var latest = null;             // the newest copy that is waiting for the admin to be idle
        var busy = false, stopped = false;
        var pill = null, chip = null;

        function signature(doc) {
            var m = doc.querySelector('main.main-content');
            var n = doc.querySelector('.sidebar-nav');
            return (m ? m.innerHTML : '') + '\u0001' + (n ? n.innerHTML : '');
        }

        // True while swapping the page would get in the admin's way.
        function adminIsBusy() {
            if (document.querySelector('dialog[open]')) return true;

            // A text box / dropdown with the cursor in it. (A ticked checkbox or a clicked button keeps
            // focus too, but that is not typing, so it must not block updates.)
            var active = document.activeElement;
            if (active && main.contains(active)) {
                var isTextField = active.tagName === 'INPUT' && !/^(checkbox|radio|submit|button|reset|image|file|range|color)$/.test(active.type);
                if (isTextField || active.tagName === 'TEXTAREA' || active.tagName === 'SELECT') return true;
            }

            var typed = main.querySelectorAll('input:not([type=hidden]):not([type=checkbox]):not([type=radio]):not([type=submit]):not([type=button]), textarea');
            for (var i = 0; i < typed.length; i++) {
                if (typed[i].value !== typed[i].defaultValue) return true;
            }

            // Ticked boxes (a bulk selection) or an open panel (e.g. the Resolve form) mean a decision is in progress.
            return !!main.querySelector('input[type=checkbox]:checked, details[open], .rp-resolve-panel:not([hidden])');
        }

        function apply(doc) {
            var fresh = doc.querySelector('main.main-content');
            var scroller = main.querySelector('.ll-scroll');
            var scrollTop = scroller ? scroller.scrollTop : 0;
            var pageTop = window.pageYOffset;

            main.innerHTML = fresh.innerHTML;

            var newScroller = main.querySelector('.ll-scroll');
            if (newScroller) newScroller.scrollTop = scrollTop;
            window.scrollTo(0, pageTop);

            var newNav = doc.querySelector('.sidebar-nav');
            if (nav && newNav) nav.innerHTML = newNav.innerHTML;

            document.dispatchEvent(new CustomEvent('live:updated'));
        }

        function setPaused(isPaused, why) {
            pill.classList.toggle('paused', isPaused);
            pill.lastChild.textContent = isPaused ? (why || 'Paused') : 'Live';
        }

        async function check() {
            if (stopped || busy || document.hidden) return;
            busy = true;

            try {
                var response = await fetch(window.location.href, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
                    credentials: 'same-origin',
                    cache: 'no-store',
                    redirect: 'manual'
                });

                // Signed out / session expired: stop quietly instead of bouncing to the login page.
                if (response.type === 'opaqueredirect' || response.status === 401 || response.status === 419) {
                    stopped = true;
                    setPaused(true, 'Signed out');
                    return;
                }
                if (!response.ok) return;

                var doc = new DOMParser().parseFromString(await response.text(), 'text/html');
                if (!doc.querySelector('main.main-content[data-live]')) return;

                var sig = signature(doc);
                setPaused(false);

                if (baseline === null) { baseline = sig; return; }   // first look = the page as it is now
                if (sig === baseline) { latest = null; chip.hidden = true; return; }

                if (adminIsBusy()) {
                    latest = { doc: doc, sig: sig };
                    chip.hidden = false;
                    return;
                }

                apply(doc);
                baseline = sig;
                latest = null;
                chip.hidden = true;
            } catch (error) {
                setPaused(true, 'Offline');      // no connection: try again at the next beat
            } finally {
                busy = false;
            }
        }

        function start() {
            main = document.querySelector('main.main-content[data-live]');
            if (!main) return;
            nav = document.querySelector('.sidebar-nav');

            pill = document.createElement('span');
            pill.className = 'live-pill';
            pill.title = 'This page updates by itself';
            pill.innerHTML = '<i></i>Live';
            document.body.appendChild(pill);

            chip = document.createElement('button');
            chip.type = 'button';
            chip.className = 'live-chip';
            chip.hidden = true;
            chip.textContent = 'New updates \u00b7 Refresh';
            chip.addEventListener('click', function () {
                if (!latest) return;
                apply(latest.doc);
                baseline = latest.sig;
                latest = null;
                chip.hidden = true;
            });
            document.body.appendChild(chip);

            setTimeout(check, 800);
            setInterval(check, INTERVAL);
            document.addEventListener('visibilitychange', function () {
                if (document.hidden) { setPaused(true, 'Paused'); } else { check(); }
            });
            window.addEventListener('online', check);
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', start);
        } else {
            start();
        }
    })();
</script>
