(function () {
    var key = 'smelterworks-theme';
    var stored = localStorage.getItem(key);
    var theme =
        stored === 'light' || stored === 'dark'
            ? stored
            : window.matchMedia('(prefers-color-scheme: dark)').matches
              ? 'dark'
              : 'light';
    document.documentElement.dataset.theme = theme;
})();
