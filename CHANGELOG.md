# Changelog

All notable changes to `phattarachai/mail-log-laravel` are documented here. Format loosely follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/); versioning is [SemVer](https://semver.org/).

## 0.2.0 — 2026-05-19

### Added

- **Back-to-app link in the header.** A new `mail-log.ui.back_link` config (`url` + `label`) renders a `← Back to app` link on the far left of the dashboard header. Defaults to `url('/')` so it works out of the box. Override via `MAIL_LOG_UI_BACK_URL` (string for a custom URL, `false` to hide) and `MAIL_LOG_UI_BACK_LABEL` (default `"Back to app"`).

### Changed

- **Index page typography.** Dropped `tracking-wide` / `tracking-wider` from every uppercase mini-label across `index.blade.php`, `show.blade.php`, `components/{group-row, stats-strip, recipients-accordion, attachments-list}.blade.php`. Labels stay uppercase but no longer carry expanded letter-spacing.
- **`Last sent` column no-wrap.** The Thai date format (`19 พ.ค. 69 · 15:07`) was wrapping onto three lines in narrow viewports; the `<th>` and `<td>` now carry `whitespace-nowrap`.

### Compatibility

- No schema, listener, or config-key removals. Drop-in upgrade from 0.1.x — no migration, no `mail-log:install` rerun required.

## 0.1.0 — 2026-05-19

Initial release.

### Added

- **Group/event split.** `mail_log_groups` (one row per fingerprint, UNIQUE) + `mail_logs` (one row per `Mail::send()`, cascade-deletes with parent group).
- **Fingerprint on Mailable class + model.** Default mode `['class', 'model']` — same template + same Eloquent record always groups together regardless of per-recipient body variation. Override per-Mailable via `mailLogFingerprintMode()`.
- **`HasMailLog` trait.** Drop into a Mailable; stamps `X-Mail-*` metadata headers the listener reads to build the fingerprint. Hooks: `mailLogModel`, `mailLogNotificationClass`, `mailLogFingerprintHints`, `mailLogFingerprintMode`, `mailLogSkip`.
- **`/mail-log` dashboard.** Pulse-style header, group index with filters (search across subject / mailable class / recipient, status, has-failures), group detail with sandboxed body preview (HTML / Text / Source toggle), Sends table with per-recipient outcomes, attachments list, Test-send modal.
- **`mail-log:install` command.** Publishes config + migrations; auto-detects a missing `media` table and publishes the Spatie laravel-medialibrary migration; writes env keys via `EnvWriter::setIfAbsent` (idempotent); prints the auth-gate / morph-map / `HasMailLog` trait / scheduled-prune snippets. `--dry-run` flag prints intended changes without writing.
- **Prunable retention.** `MailLogGroup` implements `Prunable` — schedule `model:prune` to expire idle groups past `MAIL_LOG_RETENTION_DAYS` (default 365). Events cascade-delete via FK.
- **`MailLogLinkable` contract.** Opt-in interface for host models that want a clickable deep-link in the dashboard's Model column. v0.1 ships the contract; deep-link rendering arrives in v0.2.
- **JobFailed handling.** Failed queued mailables flip the most-recent PENDING event in the matching group to FAILED + bump `failed_count` + flip `latest_status`. Falls back to class+model lookup when the queued mailable's `headers()` doesn't carry the event id.
- **Pre-built Tailwind + Alpine bundles** committed to `dist/`. Layout inlines them via `MailLog::css()` / `MailLog::js()` — no host-side `npm install`, no `storage:link`, no asset publishing. Same pattern as Horizon/Pulse.
- **Boost skill** at `resources/boost/skills/mail-log/{SKILL.md,reference.md}` — install instructions, group/event mental model, fingerprint algorithm, troubleshooting.

### Configuration

- `MAIL_LOG_ENABLED` (master switch), `MAIL_LOG_RETENTION_DAYS`, `MAIL_LOG_UI_PATH`, `MAIL_LOG_TEST_SEND_ENABLED`, `MAIL_LOG_MAX_RECIPIENTS_PER_EVENT`, `MAIL_LOG_TABLE_GROUPS`, `MAIL_LOG_TABLE_EVENTS`, `MAIL_LOG_ATTACHMENT_DISK`, `MAIL_LOG_ATTACHMENT_MAX_BYTES`, `MAIL_LOG_UI_PAGE_SIZE`, `MAIL_LOG_UI_BRAND`, `MAIL_LOG_MORPH_ALIAS`.

### Compatibility

- PHP `^8.4`
- Laravel `^12` || `^13`
- `spatie/laravel-medialibrary ^11`
