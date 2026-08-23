# SmelterWorks Panel

Extractable Laravel control plane for managed and BYOS Vintage Story servers.

## Stack

- Laravel 13
- SQLite or Postgres
- Agent handshake API (`/api/v1/agent/*`)
- Relic API (`/api/v1/relic/*`)

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

## Self-host

```bash
docker compose up -d
```

Set `PANEL_MODE=selfhost` for operator-owned deployments. Configure S3-compatible backups with standard `AWS_*` env vars.

## Security

- Argon2id password hashing
- Login throttling and account lockout
- Session fingerprint step-up for migrate and mod actions
- CSRF on browser sessions
- API tokens for Relic (Bearer auth)

Panel UI credit: [Voltaic Hosting](https://voltaic.host).
