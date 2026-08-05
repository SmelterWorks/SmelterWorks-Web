# SmelterWorks Web

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

Workflows under .github/workflows: PHP tests (8.4/8.5), Pint, PHPStan, Blade format, Vite build, GHCR publish, link check, CodeQL, dependency review. Actions are SHA-pinned. Dependabot updates Composer, npm, and Actions weekly.

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md).

## Security

See [SECURITY.md](SECURITY.md).

## License

Apache-2.0. See [LICENSE](LICENSE).
