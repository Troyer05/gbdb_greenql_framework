/* GBDB UI Shell: Verhalten der Intranet-Oberfläche (Theme, Navigation, Suche, Hinweise). */
(function () {
    'use strict';

    const THEME_KEY = 'gbdbui:theme';
    const $ = (selector, root = document) => root.querySelector(selector);
    const $$ = (selector, root = document) => Array.from(root.querySelectorAll(selector));
    const escapeHTML = value => String(value).replace(/[&<>"']/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[char]));

    function setupTheme() {
        const apply = mode => {
            document.documentElement.dataset.theme = mode;
            $$("[data-action='theme']").forEach(button => {
                button.title = mode === 'dark' ? 'Hellen Modus aktivieren' : 'Dunklen Modus aktivieren';
                button.setAttribute('aria-label', button.title);
            });
        };
        let initial = 'light';

        try {
            initial = localStorage.getItem(THEME_KEY) === 'dark' ? 'dark' : 'light';
        } catch (error) {
            initial = 'light';
        }

        apply(initial);
        $$("[data-action='theme']").forEach(button => button.addEventListener('click', () => {
            const mode = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
            apply(mode);

            try {
                localStorage.setItem(THEME_KEY, mode);
            } catch (error) {
                // Privater Modus: Darstellung gilt nur für diese Seite.
            }
        }));
    }

    function setupSidebar() {
        const sidebar = $('#sidebar');
        const overlay = $('#mobile-overlay');
        const toggle = $('[data-action="mobile-nav"]');

        if (!sidebar) {
            return;
        }

        const close = () => {
            sidebar.classList.remove('mobile-open');

            if (overlay) {
                overlay.hidden = true;
            }

            toggle?.setAttribute('aria-expanded', 'false');
        };
        const open = () => {
            sidebar.classList.add('mobile-open');

            if (overlay) {
                overlay.hidden = false;
            }

            toggle?.setAttribute('aria-expanded', 'true');
        };

        toggle?.addEventListener('click', () => (sidebar.classList.contains('mobile-open') ? close() : open()));
        overlay?.addEventListener('click', close);
        document.addEventListener('keydown', event => {
            if (event.key === 'Escape') {
                close();
            }
        });
        window.addEventListener('resize', () => {
            if (window.innerWidth > 850) {
                close();
            }
        });

        const areaNav = $('.side-nav', sidebar);
        const directory = $('[data-action="nav-directory"]', sidebar);

        directory?.addEventListener('click', () => {
            const browsing = areaNav.classList.toggle('nav-browsing');
            directory.setAttribute('aria-expanded', String(browsing));
        });

        $$('.nav-group-switch', sidebar).forEach(button => button.addEventListener('click', () => {
            const group = button.closest('.nav-group');

            // Genau eine Hauptgruppe ist geöffnet.
            $$('.nav-group', sidebar).forEach(other => {
                other.classList.remove('group-open');
                $('.nav-group-switch', other)?.setAttribute('aria-expanded', 'false');
                $('.nav-group-switch', other)?.classList.remove('selected');
                const submenu = $('.nav-submenu', other);

                if (submenu) {
                    submenu.hidden = true;
                }
            });

            areaNav?.classList.remove('nav-browsing');
            directory?.setAttribute('aria-expanded', 'false');
            group.classList.add('group-open');
            button.classList.add('selected');
            button.setAttribute('aria-expanded', 'true');
            const submenu = $('.nav-submenu', group);

            if (submenu) {
                submenu.hidden = false;
            }
        }));
    }

    function setupNotifications() {
        const popover = $('#notifications-popover');
        const button = $('[data-action="notifications"]');

        if (!popover || !button) {
            return;
        }

        const hide = () => {
            popover.hidden = true;
            button.setAttribute('aria-expanded', 'false');
        };

        button.addEventListener('click', () => {
            popover.hidden = !popover.hidden;
            button.setAttribute('aria-expanded', String(!popover.hidden));
        });
        document.addEventListener('click', event => {
            if (!button.contains(event.target) && !popover.contains(event.target)) {
                hide();
            }
        });
        document.addEventListener('keydown', event => {
            if (event.key === 'Escape') {
                hide();
            }
        });
    }

    function setupCommand() {
        const dialog = $('#command-dialog');
        const input = $('#command-input');
        const results = $('#command-results');

        if (!dialog || !input || !results) {
            return;
        }

        const links = () => $$('.side-nav a.nav-link').map(link => [link.textContent.trim(), link.getAttribute('href')]).filter(entry => entry[0] && entry[1]);
        let available = [];
        let filtered = [];
        let active = 0;
        const render = () => {
            const term = input.value.trim().toLocaleLowerCase('de');
            filtered = available.filter(entry => entry[0].toLocaleLowerCase('de').includes(term));
            active = Math.min(active, Math.max(0, filtered.length - 1));
            results.innerHTML = filtered.length
                ? filtered.map((entry, index) => `<button type="button" class="command-result ${index === active ? 'active' : ''}" data-command-index="${index}"><span>${escapeHTML(entry[0])}</span><span aria-hidden="true">↗</span></button>`).join('')
                : '<p class="command-empty">Kein passender Bereich gefunden.</p>';
        };
        const open = () => {
            if (dialog.open) {
                return;
            }

            available = links();
            input.value = '';
            active = 0;
            render();
            dialog.showModal();
            input.focus();
        };

        document.addEventListener('keydown', event => {
            if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
                event.preventDefault();
                open();
            }

            if (!dialog.open) {
                return;
            }

            if (event.key === 'ArrowDown') {
                event.preventDefault();
                active = Math.min(active + 1, filtered.length - 1);
                render();
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                active = Math.max(0, active - 1);
                render();
            } else if (event.key === 'Enter' && filtered.length) {
                event.preventDefault();
                window.location.assign(filtered[active][1]);
            }
        });
        $('[data-action="command"]')?.addEventListener('click', open);
        input.addEventListener('input', () => {
            active = 0;
            render();
        });
        results.addEventListener('click', event => {
            const target = event.target.closest('[data-command-index]');

            if (target) {
                window.location.assign(filtered[Number(target.dataset.commandIndex)][1]);
            }
        });
        dialog.addEventListener('click', event => {
            if (event.target === dialog) {
                dialog.close();
            }
        });
    }

    function setupConfirmations() {
        // Formulare mit data-confirm fragen vor folgenreichen Aktionen nach.
        $$('form[data-confirm]').forEach(form => form.addEventListener('submit', event => {
            if (!window.confirm(form.dataset.confirm)) {
                event.preventDefault();
            }
        }));
    }

    function setupUpdateTick() {
        // Fällige Framework-Update-Suche im Hintergrund anstoßen (ohne Cronjob, ohne Wartezeit beim Seitenaufbau).
        const tick = document.querySelector('[data-gbdb-update-tick]');

        if (tick) {
            const body = new URLSearchParams({ update_action: 'tick', ajax: '1', csrf: tick.dataset.csrf });
            fetch(tick.dataset.url, { method: 'POST', body, credentials: 'same-origin', keepalive: true }).catch(() => {});
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        setupTheme();
        setupSidebar();
        setupNotifications();
        setupCommand();
        setupConfirmations();
        setupUpdateTick();
    });
}());
