const storageKey = 'smelterworks-theme';

const resolveTheme = () => {
    const stored = localStorage.getItem(storageKey);

    if (stored === 'light' || stored === 'dark') {
        return stored;
    }

    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
};

const applyTheme = (theme) => {
    document.documentElement.dataset.theme = theme;

    document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
        button.setAttribute(
            'aria-label',
            theme === 'dark' ? 'Switch to light theme' : 'Switch to dark theme',
        );
    });
};

const setTheme = (theme, persist = true) => {
    applyTheme(theme);

    if (persist) {
        localStorage.setItem(storageKey, theme);
    }
};

applyTheme(document.documentElement.dataset.theme || resolveTheme());

document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        const next = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
        setTheme(next);
    });
});

const colorSchemeQuery = window.matchMedia('(prefers-color-scheme: dark)');
const syncSystemTheme = () => {
    if (localStorage.getItem(storageKey)) {
        return;
    }

    setTheme(colorSchemeQuery.matches ? 'dark' : 'light', false);
};

if (typeof colorSchemeQuery.addEventListener === 'function') {
    colorSchemeQuery.addEventListener('change', syncSystemTheme);
} else if (typeof colorSchemeQuery.addListener === 'function') {
    colorSchemeQuery.addListener(syncSystemTheme);
}
