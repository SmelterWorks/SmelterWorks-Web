/** @type {import('@lhci/cli').Config} */
module.exports = {
    ci: {
        collect: {
            url: [
                'http://127.0.0.1:8765/',
                'http://127.0.0.1:8765/hosting',
                'http://127.0.0.1:8765/relic',
                'http://127.0.0.1:8765/mods',
                'http://127.0.0.1:8765/projects',
                'http://127.0.0.1:8765/about',
                'http://127.0.0.1:8765/donate',
            ],
            numberOfRuns: 3,
            startServerCommand: './bin/lighthouse-serve',
            startServerReadyPattern: 'Development Server \\(http',
            settings: {
                preset: 'desktop',
                chromeFlags: '--no-sandbox --headless --disable-dev-shm-usage',
            },
        },
        assert: {
            assertions: {
                'categories:performance': ['error', { minScore: 1, aggregationMethod: 'median' }],
                'categories:accessibility': ['error', { minScore: 1, aggregationMethod: 'median' }],
                'categories:best-practices': ['error', { minScore: 1, aggregationMethod: 'median' }],
                'categories:seo': ['error', { minScore: 1, aggregationMethod: 'median' }],
            },
        },
        upload: {
            target: 'filesystem',
            outputDir: '.lighthouseci',
        },
    },
};
