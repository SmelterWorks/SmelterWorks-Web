# SmelterWorks Web

Laravel + Blade site for SmelterWorks: open-source Vintage Story tools, mods, Relic Launcher, and hosting catalog.

Licensed under the Apache License, Version 2.0. See `LICENSE`.

## Stack

- PHP 8.4+
- Laravel 13
- Blade
- Vite + Tailwind CSS 4

## Local setup

```bash
composer install
cp .env.example .env
./bin/php artisan key:generate
./bin/php artisan migrate --seed
npm install
npm run build
./bin/serve
```

Set deploy-specific links in `.env` (`SMELTERWORKS_FLUXER_URL`, `SMELTERWORKS_PANEL_URL`, `SMELTERWORKS_CONTACT_EMAIL`, and so on). Do not commit real invites, keys, or secrets. This repository is public.

See `CONTRIBUTING.md` for PR expectations and `SECURITY.md` for vulnerability reports.

Arch/CachyOS PHP often ships with `pdo_sqlite` commented out. This repo uses `./bin/php` and `./bin/serve`, which load `php-local.ini` so SQLite works for artisan and the built-in server.

Do not run plain `php artisan serve` unless you have enabled `pdo_sqlite` and `sqlite3` in system PHP.

## Project layout

| Path | Role |
| --- | --- |
| `app/Http/Controllers` | Thin HTTP adapters |
| `app/Data` | Immutable view/domain DTOs |
| `app/Support/Content` | Content catalog services |
| `app/Support/Hosting` | Hosting stock and purchases |
| `app/Support/Platform` | Relic download User-Agent detection |
| `config/smelterworks.php` | Public site copy, nav, project catalog |
| `config/smelterworks/` | Split catalogs: `hosting.php`, `relic.php`, `projects.php` |
| `public/icons` | Synced icon SVGs (Simple Icons, Font Awesome brands, Lucide) |
| `scripts/sync-icons.mjs` | Copies icon packs into `public/icons` |
| `docker/` | Nginx, PHP-FPM, and entrypoint for the container image |
| `resources/css/site/` | Split page styles imported by `resources/css/app.css` |
| `resources/views/pages` | Page templates |
| `resources/views/components` | Reusable Blade UI |
| `routes/web.php` | Named public routes |
| `.agents/skills/` | Agent writing and project skills |

Add projects in `config/smelterworks.php`. Use `page_route` when a catalog entry should open a dedicated page (Relic uses `relic`). Replace `ProjectCatalog` with an Eloquent-backed store when you need admin editing or a database.

`npm run build` runs `icons:sync` first so brand and UI icons stay current.

## Docker

Rootless-friendly image and compose files ship in the repo:

```bash
# Set APP_KEY in .env first
docker compose build
docker compose up -d
```

The runtime listens on port 8080, drops capabilities, uses a read-only root filesystem, and health-checks `/up`.

For Coolify, use `docker-compose.coolify.yml` as the Compose file. It skips host port publishes, routes through Coolify's proxy with `SERVICE_URL_WEB_8080`, and surfaces `APP_KEY` plus optional `SMELTERWORKS_*` links in the Coolify UI. Set `APP_KEY` before the first deploy (`php artisan key:generate --show`).

## CI/CD

GitHub Actions runs on pushes and pull requests to `main`:

| Workflow | Purpose |
| --- | --- |
| `ci.yml` | PHP 8.4/8.5 tests, Pint, PHPStan, Blade format check, Vite build |
| `links.yml` | Dead link check on docs, config URLs, and Blade templates |
| `codeql.yml` | CodeQL for PHP, JavaScript, and Actions |
| `dependency-review.yml` | Blocks PRs that add vulnerable dependencies |

Actions are pinned to full commit SHAs per [GitHub Actions secure use](https://docs.github.com/en/actions/reference/security/secure-use). Dependabot opens weekly update PRs for Composer, npm, and Actions.

## Agent writing rules

User-facing copy should follow the [no_ai_slop_writing_rules](https://github.com/realrossmanngroup/no_ai_slop_writing_rules) skills in `.agents/skills/`. See `AGENTS.md` and `.agents/skills/smelterworks-web/SKILL.md`.

## Useful commands

```bash
npm run icons:sync
npm run format
npm run lint
npm run dev
composer format
composer lint
composer test
./bin/php artisan route:list
```
