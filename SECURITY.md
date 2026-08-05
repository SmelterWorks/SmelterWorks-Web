# Security policy

SmelterWorks Web is a community project and is not affiliated with Anego Studios. Do not report security issues about this site to them.

## Reporting a vulnerability

Open a private security advisory on the GitHub repository, or email the maintainers through the repository contact options. Do not file a public issue for auth bypasses, secret exposure, or production access problems.

Include the affected route or feature, steps to reproduce, and impact.

## What this app stores

- Session and cache data use Laravel defaults. Production deployments should use env-driven drivers and volumes documented in `docker-compose.yml`.
- Do not commit `.env`, API keys, Fluxer invites, panel URLs, or other deploy-only values. This repository is public under Apache 2.0.
- Hosting checkout and panel flows are gated while products are still coming soon. Do not expose unfinished purchase paths in production config.

## Scope notes

Public pages link to external projects (GitHub, Ko-Fi, Vintage Story, Relic releases). Treat third-party sites under their own policies. Optional env links (Fluxer, panel, wiki) are hidden in the UI when unset.
