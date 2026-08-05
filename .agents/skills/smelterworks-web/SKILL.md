---
name: smelterworks-web
description: Product and architecture context for the SmelterWorks public website, hosting catalog, Relic downloads, panel stub, and open-source hygiene. Use when editing this Laravel site, site copy, hosting/relic/panel pages, or agent docs.
---

# SmelterWorks Web context

## What this repo is

Laravel 13 + Blade marketing and catalog site for SmelterWorks.

Ships:

- Public website (home, projects, mods, about, contribute)
- Contact (`/contact`) and donate (`/donate`) pages
- Hosting catalog (plans, regions, refunds). Purchases stay closed while `config('smelterworks.hosting.coming_soon')` is true
- Relic Launcher pages (`/relic`, `/relic/download` with UA platform detect)
- Panel entry (`/panel`) as an in-app stub, or redirect when `SMELTERWORKS_PANEL_URL` is set
- Privacy and terms (no ads, tracking, or telemetry; functional cookies only)
- Rootless Docker image (`Dockerfile`, `docker-compose.yml`)

License: Apache 2.0 (`LICENSE`). Assume the tree is public.

## What the panel is

The control panel is the future home for account login, server provisioning, mod installs, backups, and the file editor. It is not a separate product name. Until features land, keep `/panel` honest as a placeholder or external URL from env.

Do not invent live panel features in copy.

## Hosting copy rules

- Name regions (United States, Germany) and plan hardware (CPU model, DDR5 ECC, NVMe) when true
- Do not publish fleet size, "N hosts", per-host RAM headroom, or capacity planning notes on public pages
- Stock numbers in config drive inventory when purchases open. Keep operational commentary out of Blade

## Open-source secrets hygiene

- Never hardcode Fluxer invites, API keys, tokens, passwords, private hostnames, or deploy URLs in the repo
- Use `.env` / `.env.example` keys such as `SMELTERWORKS_FLUXER_URL`, `SMELTERWORKS_PANEL_URL`, `SMELTERWORKS_LEGAL_CONTACT`, `SMELTERWORKS_CONTACT_EMAIL`
- Hide empty optional links in Blade (`@if (filled(...))`)
- Contact email display uses `App\Support\ContactEmail::obfuscate()` (`[at]` and `[dot]` in the visible text, real address in `mailto:`)
- Tests may set fake URLs in `phpunit.xml`. Production values stay in operator env or CI secrets

## Where content lives

Most public copy lives under `config/smelterworks.php` and focused files in `config/smelterworks/` (`hosting.php`, `relic.php`, `projects.php`). Controllers stay thin.

- Relic download detection: `App\Support\Platform\PlatformDetector`
- Relic releases: stable buttons use `{repo}/releases/latest`; nightlies resolve `nightly-YYYYMMDD` prereleases via GitHub API
- Hosting index view data: `App\Support\Hosting\HostingIndexPresenter`
- Hosting RSS: `HostingFeedController` + `HostingFeedBuilder`
- Catalog projects may set `page_route` (Relic uses `relic`) so list links open the dedicated page
- Icons: sync from npm packs with `scripts/sync-icons.mjs` into `public/icons/{simple,brands,lucide,flags}`. Do not hand-author icon path data
- Feature lists on Relic and hosting use `<x-feature-grid>` with Lucide icon names from config
- Styles live in `resources/css/site/*.css` and are imported from `app.css`

## Writing

Follow `.agents/skills/no-ai-slop` and `.agents/skills/rossmann-voice` for user-facing prose. No emdashes. No fabricated facts. No affiliation claims with Anego Studios.
