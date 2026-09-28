(function () {
    var storageKey = 'azurekiln-link-theme';
    var root = document.documentElement;

    function preferredTheme() {
        try {
            var stored = window.localStorage.getItem(storageKey);
            if (stored === 'dark' || stored === 'light') return stored;
        } catch (error) {}
        return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }

    function applyTheme(theme) {
        var dark = theme === 'dark';
        root.classList.toggle('ak-dark', dark);
        document.body.classList.toggle('dark-mode', dark);
        document.querySelectorAll('[data-theme-icon]').forEach(function (icon) {
            icon.textContent = dark ? 'light_mode' : 'dark_mode';
        });
        document.querySelectorAll('[data-theme-toggle]').forEach(function (button) {
            button.setAttribute('aria-label', dark ? '切换浅色模式' : '切换深色模式');
        });
    }

    applyTheme(preferredTheme());
    document.addEventListener('DOMContentLoaded', function () {
        applyTheme(preferredTheme());
        document.querySelectorAll('[data-theme-toggle]').forEach(function (button) {
            button.addEventListener('click', function () {
                var next = root.classList.contains('ak-dark') ? 'light' : 'dark';
                try { window.localStorage.setItem(storageKey, next); } catch (error) {}
                applyTheme(next);
            });
        });
        document.querySelectorAll('[data-bookmarklet-open]').forEach(function (button) {
            button.addEventListener('click', function () {
                var modal = document.getElementById('bookmarklet-modal');
                if (modal) modal.hidden = false;
            });
        });
        document.querySelectorAll('[data-bookmarklet-close]').forEach(function (button) {
            button.addEventListener('click', function () {
                var modal = document.getElementById('bookmarklet-modal');
                if (modal) modal.hidden = true;
            });
        });
        var modal = document.getElementById('bookmarklet-modal');
        if (modal) modal.addEventListener('click', function (event) {
            if (event.target === modal) modal.hidden = true;
        });
    });
}());
