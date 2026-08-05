# SmelterWorks Web

<!-- BADGES:BEGIN -->
[![CI](https://img.shields.io/github/actions/workflow/status/SmelterWorks/SmelterWorks-Web/ci.yml?branch=main&label=CI&labelColor=1c1916&color=b45309&logo=githubactions)](https://github.com/SmelterWorks/SmelterWorks-Web/actions/workflows/ci.yml) [![Lighthouse CI](https://img.shields.io/github/actions/workflow/status/SmelterWorks/SmelterWorks-Web/lighthouse.yml?branch=main&label=Lighthouse+CI&labelColor=1c1916&color=b45309&logo=lighthouse)](https://github.com/SmelterWorks/SmelterWorks-Web/actions/workflows/lighthouse.yml) [![Lighthouse](https://img.shields.io/endpoint?url=https%3A%2F%2Fraw.githubusercontent.com%2FSmelterWorks%2FSmelterWorks-Web%2Fmain%2F.github%2Fbadges%2Flighthouse.json)](https://github.com/SmelterWorks/SmelterWorks-Web/actions/workflows/lighthouse.yml) [![Docker](https://img.shields.io/github/actions/workflow/status/SmelterWorks/SmelterWorks-Web/docker.yml?branch=main&label=Docker&labelColor=1c1916&color=b45309&logo=docker)](https://github.com/SmelterWorks/SmelterWorks-Web/actions/workflows/docker.yml) [![links](https://img.shields.io/github/actions/workflow/status/SmelterWorks/SmelterWorks-Web/links.yml?branch=main&label=links&labelColor=1c1916&color=b45309)](https://github.com/SmelterWorks/SmelterWorks-Web/actions/workflows/links.yml) [![CodeQL](https://img.shields.io/github/actions/workflow/status/SmelterWorks/SmelterWorks-Web/codeql.yml?branch=main&label=CodeQL&labelColor=1c1916&color=b45309&logo=github)](https://github.com/SmelterWorks/SmelterWorks-Web/actions/workflows/codeql.yml) [![PHP](https://img.shields.io/badge/?style=flat-square&labelColor=1c1916&label=PHP&message=8.4%2B&color=3a342e)](https://github.com/SmelterWorks/SmelterWorks-Web) [![Laravel](https://img.shields.io/badge/?style=flat-square&labelColor=1c1916&label=Laravel&message=13&color=b45309)](https://github.com/SmelterWorks/SmelterWorks-Web) [![GHCR](https://img.shields.io/badge/?style=flat-square&labelColor=1c1916&label=GHCR&message=smelterworks-web&color=3f5a4d&logo=docker&logoColor=white)](https://github.com/SmelterWorks/SmelterWorks-Web/pkgs/container/smelterworks-web) [![license](https://img.shields.io/badge/?style=flat-square&labelColor=1c1916&label=license&message=Apache+2.0&color=3f5a4d)](https://github.com/SmelterWorks/SmelterWorks-Web/blob/main/LICENSE)
<!-- BADGES:END -->

Public site for [SmelterWorks](https://github.com/SmelterWorks): Vintage Story tools, mods, [Relic Launcher](https://github.com/SmelterWorks/Relic-Launcher), and hosting catalog.

SmelterWorks is not affiliated with Anego Studios.

Stack: PHP 8.4+, Laravel 13, Blade, Vite, Tailwind CSS 4. Apache License 2.0.

## Requirements

- PHP 8.4+ with pdo_sqlite / sqlite3
- Composer 2
- Node.js 22+ (Vite build and icon sync)

## Build and run

```bash
composer install
cp .env.example .env
./bin/php artisan key:generate
./bin/php artisan migrate
npm ci
npm run build
./bin/serve
```

Tests, format, lint:

```bash
composer test
composer format && composer lint
npm run format && npm run lint
```

Set SMELTERWORKS_* URLs in .env for Fluxer, panel, contact, and related links. Leave blank to hide. Do not commit secrets. This repo is public.

## Docker

Image listens on 8080. Set APP_KEY in .env first.

```bash
docker compose build
docker compose up -d
```

Coolify: point the stack at docker-compose.coolify.yml. Pulls ghcr.io/smelterworks/smelterworks-web. Set APP_KEY (and optional IMAGE_TAG). Do not override the container user. Publish the GHCR package as public, or supply a pull token.

## Project map

| Path | Role |
| --- | --- |
| app/Http/Controllers | HTTP adapters |
| app/Data | Immutable DTOs |
| app/Support | Content, hosting, currency, platform helpers |
| config/smelterworks.php | Site copy, nav, catalog entrypoint |
| config/smelterworks/ | hosting, relic, projects catalogs |
| resources/views | Blade pages and components |
| resources/css/site/ | Page styles |
| routes/web.php | Public routes |
| docker/ | Nginx, PHP-FPM, entrypoint |
| scripts/sync-icons.mjs | Icon pack sync into public/icons |

Catalog entries live under config/smelterworks. Use page_route when an entry should open a dedicated page (Relic uses relic).

## CI

Workflows under .github/workflows: PHP tests (8.4/8.5), Pint, PHPStan, Blade format, Vite build, Lighthouse CI, GHCR publish, link check, CodeQL, dependency review. Actions are SHA-pinned. Dependabot updates Composer, npm, and Actions weekly.

README badges are generated from `docs/badges.json`. After changing badge config, run `npm run badges`. Lighthouse score badge updates from CI on `main` pushes.

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md).

## Security

See [SECURITY.md](SECURITY.md).

## License

Apache-2.0. See [LICENSE](LICENSE).
