import './theme.js';

const revealElements = document.querySelectorAll('[data-reveal]');

if (revealElements.length > 0) {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        revealElements.forEach((element) => element.classList.add('is-visible'));
    } else if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (!entry.isIntersecting) {
                        return;
                    }

                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                });
            },
            { threshold: 0.2 },
        );

        revealElements.forEach((element) => observer.observe(element));
    } else {
        revealElements.forEach((element) => element.classList.add('is-visible'));
    }
}

const menuToggle = document.querySelector('[data-menu-toggle]');
const mobileNav = document.querySelector('[data-mobile-nav]');

if (menuToggle && mobileNav) {
    const setMenuOpen = (open) => {
        menuToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        menuToggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
        mobileNav.hidden = !open;
        mobileNav.classList.toggle('is-open', open);
    };

    menuToggle.addEventListener('click', () => {
        const open = menuToggle.getAttribute('aria-expanded') !== 'true';
        setMenuOpen(open);
    });

    mobileNav.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => setMenuOpen(false));
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            setMenuOpen(false);
        }
    });

    const desktopQuery = window.matchMedia('(min-width: 768px)');
    const syncMenuForViewport = () => {
        if (desktopQuery.matches) {
            setMenuOpen(false);
        }
    };

    if (typeof desktopQuery.addEventListener === 'function') {
        desktopQuery.addEventListener('change', syncMenuForViewport);
    } else if (typeof desktopQuery.addListener === 'function') {
        desktopQuery.addListener(syncMenuForViewport);
    }
}

const currencySwitcher = document.querySelector('[data-currency-switcher]');

if (currencySwitcher) {
    const buttons = currencySwitcher.querySelectorAll('[data-currency]');
    const priceNodes = document.querySelectorAll('[data-price]');

    const formatMoney = (amount, currency) => {
        const value = Number(amount);

        if (Number.isNaN(value)) {
            return '';
        }

        return new Intl.NumberFormat(undefined, {
            style: 'currency',
            currency,
            maximumFractionDigits: 2,
        }).format(value);
    };

    const setCurrency = (currency) => {
        buttons.forEach((button) => {
            const active = button.dataset.currency === currency;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });

        priceNodes.forEach((node) => {
            const amount = currency === 'EUR' ? node.dataset.eur : node.dataset.usd;

            if (!amount) {
                return;
            }

            node.textContent = formatMoney(amount, currency);
        });
    };

    buttons.forEach((button) => {
        button.addEventListener('click', () => {
            if (button.disabled) {
                return;
            }

            setCurrency(button.dataset.currency);
        });
    });
}

document.querySelectorAll('[data-branding-mark]').forEach((mark) => {
    const preview = mark.querySelector('[data-branding-preview]');
    const download = mark.querySelector('[data-branding-download]');
    const buttons = mark.querySelectorAll('[data-branding-format]');

    if (!preview || !download || buttons.length === 0) {
        return;
    }

    const setFormat = (button) => {
        const format = button.dataset.brandingFormat;

        buttons.forEach((entry) => {
            const active = entry === button;
            entry.classList.toggle('is-active', active);
            entry.setAttribute('aria-pressed', active ? 'true' : 'false');
        });

        preview.src = button.dataset.url;
        download.href = button.dataset.url;
        download.download = button.dataset.filename;
        download.textContent = `Download ${format.toUpperCase()}`;
    };

    buttons.forEach((button) => {
        button.addEventListener('click', () => setFormat(button));
    });
});
