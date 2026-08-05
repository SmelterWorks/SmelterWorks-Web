# SmelterWorks Web

Guidelines for AI agents working in this repository.

## Project

Public marketing and catalog site for SmelterWorks (Vintage Story software, mods, Relic Launcher, and hosting).

Stack: Laravel 13 + Blade + Vite + Tailwind CSS 4.

This repo is open source under Apache 2.0 (`LICENSE`). Treat every commit as public.

### Surfaces

| Surface | Route(s) | Notes |
| --- | --- | --- |
| Website | `/`, `/projects`, `/mods`, `/about`, `/contribute` | Catalog and copy from `config/smelterworks.php` |
| Hosting | `/hosting` (+ purchase routes) | Plans and regions are public. Checkout is gated by `hosting.coming_soon` |
| Relic | `/relic`, `/relic/download` | Download page auto-detects OS from User-Agent |
| Contact | `/contact` | Email shown obfuscated (`[at]`, `[dot]`) when configured |
| Donate | `/donate` | Ko-Fi link from env |
| Panel | `/panel` | Placeholder in this app. Redirects away if `SMELTERWORKS_PANEL_URL` is set |
| Legal | `/privacy`, `/terms` | No ads, no tracking, no telemetry. Functional cookies only |
| SEO | `/robots.txt`, `/sitemap.xml` | Layout emits description, canonical, Open Graph, Twitter, JSON-LD |

The panel product (login, provisioning, mod browser, backups, file editor) is intended to live in this web app over time. Until then `/panel` is a stub or external redirect.

### Layout

- `app/Http/Controllers` thin HTTP adapters
- `app/Data` immutable DTOs (`ProjectData` supports optional `page_route`)
- `app/Support/Content` catalog services
- `app/Support/Hosting` stock and purchase services
- `app/Support/Currency` ECB/Frankfurter FX quotes
- `app/Support/Platform` Relic download UA detection
- `app/Support/ContactEmail` display obfuscation for contact addresses
- `app/Support/Seo` JSON-LD graph and sitemap URL list
- `config/smelterworks.php` site-level copy and requires under `config/smelterworks/`
- `config/smelterworks/hosting.php`, `relic.php`, `projects.php` focused catalogs
- Site banner under the navbar from `smelterworks.banner` / `<x-site-banner>` (toggle and colors via env)
- `public/icons` synced SVGs from Simple Icons, Font Awesome brands, and Lucide
- `scripts/sync-icons.mjs` icon sync (runs under `pnpm run build`)
- `scripts/sync-brand-assets.mjs` raster mark sizes (run `pnpm run brand:sync` after master PNG changes)
- `resources/css/site/` CSS modules imported from `resources/css/app.css`
- `docker/` + `Dockerfile` + `docker-compose.yml` rootless production image
- `docker-compose.coolify.yml` Coolify stack pulling `ghcr.io/smelterworks/smelterworks-web`
- `resources/views` Blade pages and components
- `routes/web.php` named public routes
- `.agents/skills/` writing and project skills

## Secrets and open-source hygiene

- Never hardcode invite links, API keys, tokens, passwords, private IPs, host counts that disclose capacity planning, or deploy-only URLs in committed PHP, Blade, JS, or docs
- Put deploy-specific values in `.env` (see `.env.example` for names only)
- Do not commit `.env`, credentials, private keys, or production dumps
- Fluxer / panel / legal contact URLs come from env. Views hide empty links
- Public copy may name regions (US, Germany) and plan specs. Do not add fleet size, RAM headroom math, or internal host inventory to user-facing pages
- Prefer placeholders in `.env.example`. Real values stay on the operator machine or CI secrets
- Do not hand-draw icon SVGs. Sync from npm icon packs via `scripts/sync-icons.mjs`

## Commands

```bash
composer install
pnpm install
pnpm run build
composer format
pnpm run format
composer lint
pnpm run lint
composer test
./bin/serve
```

CI uses PHP 8.5 on Ubuntu with required extensions. Local Arch/CachyOS setups use `./bin/php` and `php-local.ini` so SQLite loads. Prefer `./bin/serve` over bare `php artisan serve`.

Composer script `composer test` runs PHPUnit with `php-local.ini`. Format/lint: Pint + Larastan (`composer format` / `composer lint`), Blade via blade-formatter (`pnpm run format` / `pnpm run lint`).

## Writing rules for site copy

When writing or editing user-facing prose (pages, README marketing copy, contribution text), read and follow:

- `.agents/skills/no-ai-slop/SKILL.md`
- `.agents/skills/rossmann-voice/SKILL.md`
- `.agents/skills/no-ai-slop/references/ai-writing-detection.md`
- `.agents/skills/smelterworks-web/SKILL.md` for product context

Source: [no_ai_slop_writing_rules](https://github.com/realrossmanngroup/no_ai_slop_writing_rules).

Self-check prose before returning it. No emdashes, no AI filler, no dramatic headings, no fabricated facts.

## Code changes

- Match existing Laravel and Blade conventions in the repo
- Keep controllers thin, push content into config or services until a database is needed
- Run `./bin/php vendor/bin/phpunit` (or `composer test`) after PHP changes
- Run `pnpm run build` after asset or icon-pack changes
- Do not invent affiliation with Anego Studios / Vintage Story
- Keep security headers middleware, icon path allowlisting, and HTTPS-only external redirects intact

## CI/CD

GitHub Actions workflows live in `.github/workflows/`. Actions are pinned to full commit SHAs. Dependabot opens update PRs for Composer, pnpm, and Actions.

| Workflow | Purpose |
| --- | --- |
| `ci.yml` | PHP tests, Pint, Vite build |
| `lighthouse.yml` | Lighthouse CI (performance, accessibility, best practices, SEO) |
| `docker.yml` | GHCR image build and publish |
| `links.yml` | Lychee dead-link check |
| `codeql.yml` | CodeQL |
| `dependency-review.yml` | Vulnerable dependency gate on PRs |
