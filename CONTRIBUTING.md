# Contributing to SmelterWorks Web

## Before you open a PR

1. Run `composer format` and `npm run format` if you changed PHP or Blade
2. Run `composer lint` and `npm run lint`
3. Run `composer test`
4. Run `npm run build`
5. Do not commit secrets, tokens, invite links, or production `.env` values
6. Do not hand-draw icon SVGs. Add pack names to `scripts/sync-icons.mjs` and run `npm run icons:sync`

## Branch and PR shape

- One concern per PR when you can
- Describe what changed and how you tested it
- Link related issues

## Where content lives

- Public copy, nav, and catalog entries: `config/smelterworks.php` plus `config/smelterworks/*.php`
- Page templates: `resources/views/pages`
- Reusable UI: `resources/views/components`
- Styles: `resources/css/site/*.css` (imported from `resources/css/app.css`)
- Deploy-only URLs and contact email: `.env` (see `.env.example` for names)

Keep controllers thin. Push content into config or services until a database is needed.

## Code style

- PHP: Laravel Pint (`composer format` / `composer lint`)
- Static analysis: Larastan / PHPStan (`composer analyse`)
- Blade: blade-formatter (`npm run format` / `npm run lint`)
- Match existing Laravel and Blade conventions in the repo
- Comments only where the why is non-obvious. No TODO markers
- User-facing prose follows `.agents/skills/no-ai-slop/` and `.agents/skills/rossmann-voice/`

## AI-assisted contributions

- You are the author. Review the full diff before opening a PR. Be able to explain and defend the change in review.
- Unreviewed agent dumps and drive-by LLM patches will be closed.
- Optional: note the tool in the PR description (for example `Assisted-by: OpenCode`). Do not use `Co-authored-by` for a model.

## Agent context

Skills for AI agents are in `.agents/skills/`. See `AGENTS.md` for repo layout and secrets hygiene.

## Security notes for workflows

Workflows pin third-party actions to full commit SHAs. Prefer `env:` for untrusted values. Do not add `pull_request_target` without a documented threat model.
