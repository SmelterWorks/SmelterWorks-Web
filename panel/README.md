# SmelterWorks Panel

Extractable Laravel control plane for managed and BYOS Vintage Story servers.

## Stack

- Laravel 13
- SQLite, MySQL/MariaDB, or PostgreSQL
- Agent handshake API (`/api/v1/agent/*`)
- Relic API (`/api/v1/relic/*`)
- Sentry or GlitchTip error tracking (Sentry-compatible DSN)
- Prometheus metrics endpoint

## Setup

```bash
cd panel
composer install
cp .env.example .env
../bin/php artisan key:generate
../bin/php artisan migrate
../bin/php artisan serve
```

Run tests:

```bash
composer test
```

Validate database and observability settings:

```bash
php artisan panel:doctor
```

## Database

Set `DB_CONNECTION` to `sqlite`, `mysql`, `mariadb`, or `pgsql`. Examples are in `.env.example`.

For production, use MySQL or PostgreSQL. SQLite remains supported for local development and small self-host installs.

## Observability

### Error tracking

Set `SENTRY_LARAVEL_DSN` to a Sentry or GlitchTip project DSN. GlitchTip uses the same Sentry protocol:

```env
SENTRY_LARAVEL_DSN=https://<key>@glitchtip.example.com/<project_id>
SENTRY_TRACES_SAMPLE_RATE=0.1
```

### Metrics

Enable Prometheus scraping with:

```env
METRICS_ENABLED=true
METRICS_TOKEN=your-secret-token
```

Scrape `GET /metrics` with the bearer token or `?token=` query parameter.

## Self-host

SQLite (default):

```bash
docker compose up -d
```

PostgreSQL:

```bash
docker compose --profile postgres up -d panel-postgres
```

MySQL:

```bash
docker compose --profile mysql up -d panel-mysql
```

Set `PANEL_MODE=selfhost` for operator-owned deployments. Configure S3-compatible backups with standard `AWS_*` env vars.

## Security

- Argon2id password hashing
- Login throttling and account lockout
- Session fingerprint step-up for migrate and mod actions
- CSRF on browser sessions
- API tokens for Relic (Bearer auth)

Panel UI credit: [Voltaic Hosting](https://voltaic.host).
