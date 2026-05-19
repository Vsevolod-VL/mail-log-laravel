# Mail Log Laravel — agent guide

## Scope

Self-hosted outbound mail logger for Laravel. Captures every `Mail::send()` + `Notification` → mail channel send, groups them by Mailable class + originating Eloquent model (not by body bytes — same template + model = same group regardless of per-recipient signed URLs, magic tokens, personalized salutations), and ships its own Tailwind dashboard at `/mail-log`. Zero Filament / Livewire / Vue dependencies in the host app.

## Install path (target shape, Phase 5)

```bash
composer require phattarachai/mail-log-laravel
php artisan mail-log:install
```

The full installer lands in Phase 5. Until then `mail-log:install` is a stub that exits 0 — provider auto-discovery still wires everything else (config merge, migrations, routes, listener bindings).

## Architecture in one paragraph

Two tables. `mail_log_groups` (UNIQUE on `fingerprint`, one row per Mailable+model identity) holds dedup identity + denormalized counters + a representative body preview. `mail_logs` (events, FK to group, `cascadeOnDelete`) holds one row per actual send with per-recipient `to`/`cc`/`bcc` + status + timing. The `LogOutgoingMail` listener `firstOrCreate`s the group on `MessageSending` and appends an event; `MessageSent` / `JobFailed` flip the event status and bump group counters atomically. Same shape Watchtower uses for `issue_groups` + `events`.

## Repo layout

```
src/
    MailLog.php                       static css() / js() / auth() / check() / flushAuth()
    MailLogServiceProvider.php
    Models/{MailLogGroup,MailLog}.php (Phase 2)
    Enums/MailLogStatus.php           (Phase 2)
    Concerns/HasMailLog.php           (Phase 2) — trait for host Mailables
    Listeners/LogOutgoingMail.php
    Support/Fingerprinter.php         class + model primary, body-hash fallback
    Http/{Controllers,Middleware}/
    Console/InstallCommand.php
config/mail-log.php
resources/
    css/mail-log.css · js/mail-log.js  ← sources
    views/                              ← Blade dashboard (Phase 4b)
    boost/skills/mail-log/               ← Boost skill (Phase 5)
routes/web.php
database/{migrations,factories}/      (Phase 2)
dist/mail-log.{css,js}                committed → inlined by MailLog::css()/js()
tests/{Pest.php, TestCase.php, Feature/, Unit/}
```

## Build assets (Horizon/Pulse pattern)

```bash
npm install
npm run build           # writes dist/mail-log.{css,js}
npm run watch           # rebuild on save while iterating UI
```

`dist/` is **committed to git** and inlined by `MailLog::css()` / `MailLog::js()` via `file_get_contents()` + `HtmlString`. No HTTP route for assets, no `storage:link`. Re-run `npm run build` + commit `dist/` before tagging a release.

## Test

```bash
composer install
vendor/bin/pest --compact
vendor/bin/pest --filter ServiceProviderTest   # focused
```

Testbench harness in `tests/TestCase.php` registers `MailLogServiceProvider` + Spatie's `MediaLibraryServiceProvider` and resets `MailLog::auth` in `setUp()` so each test starts with the debug-only default gate.

## Conventions

- PHP 8.4, `declare(strict_types=1)` on every file, explicit return types + param hints, constructor property promotion.
- `Phattarachai\MailLogLaravel\` PSR-4 root.
- Tests: `Tests\Feature` + `Tests\Unit` namespaces, Pest syntax, one assertion cluster per `it(...)`.
- The fingerprint algorithm is documented in `.ai/tasks/2605/18-mail-log-package/1-overview.md` (in the host watchtower repo) — keep `Fingerprinter` and the `HasMailLog` trait in lock-step with that spec.

## Phase status

| Phase | Scope | Status |
|-------|-------|--------|
| 1     | Repo skeleton · composer · vite/tailwind · provider · static helper · auth middleware · routes · Testbench harness | ✅ shipped |
| 2     | Two migrations · two models · enum · factories · `HasMailLog` trait                                              | 🔲 next  |
| 3     | `Fingerprinter` + `LogOutgoingMail` listener + `MailLogGroup::recordEvent`                                       | 🔲       |
| 4     | UI design (mocks)                                                                                                | ✅ done in host repo |
| 4b    | UI build: Blade layout + Alpine + controllers + `dist/` build                                                    | 🔲       |
| 5     | `mail-log:install` command + `EnvWriter` port + Boost skill                                                      | 🔲       |
| 6     | Verification: full Pest run + Postgres + MySQL + SQLite + dogfood in nectapharma                                 | 🔲       |
| 7     | Release `v0.1.0` + tag + push + Packagist auto-sync                                                              | 🔲       |

## Release

```bash
# 1. Bump version in composer.json (semver — first cut = 0.1.0).
# 2. Build + commit dist/.
npm run build
git add dist/ composer.json CHANGELOG.md
git commit -m "Release v0.1.0"
# 3. Tag + push.
git tag v0.1.0
git push origin main --tags
# Packagist auto-syncs from GitHub webhook (configured at repo creation time).
```

## Canonical skill

Phase 5 ships `resources/boost/skills/mail-log/{SKILL.md,reference.md}` — Laravel Boost picks it up on `php artisan boost:install --skills` in host projects.
