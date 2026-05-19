# Mail Log

[![Packagist Version](https://img.shields.io/packagist/v/phattarachai/mail-log-laravel.svg?style=flat-square)](https://packagist.org/packages/phattarachai/mail-log-laravel)
[![License](https://img.shields.io/packagist/l/phattarachai/mail-log-laravel.svg?style=flat-square)](LICENSE.md)

Self-hosted outbound mail logger for Laravel. Captures every `Mail::send(...)` + Notification mail-channel call, groups identical sends together by Mailable class + Eloquent model, and exposes the result at a `/mail-log` dashboard. Ships its own Tailwind UI — no Filament, no Livewire-from-the-package, no `npm install` in the host.

## What it gives you

- **One row per template, not per send.** Sending `OrderShippedMail($order)` to ten recipients lands as **one group with `sent_count=10`** + ten event rows. Different `Order` → different group.
- **A `/mail-log` dashboard** styled like Laravel Horizon / Pulse: list of groups (last-sent first), per-group detail page with body preview (sandboxed `<iframe srcdoc>`), Sends table with per-recipient outcomes, attachments, "Test send" modal.
- **Per-mailable opt-in via a single trait** — drop `HasMailLog` into a Mailable, return the originating model from `mailLogModel()`, return `$this->withMailLog(new Headers())` from `headers()`. That's the whole integration surface.
- **Failure capture without queue gymnastics.** A `JobFailed` listener locates the most-recent PENDING event for the matching class+model and flips it to FAILED with the exception message.
- **First-class retention.** `MailLogGroup` implements `Prunable`; `php artisan model:prune` (daily via scheduler) cascades old groups + their events.

## Install

```bash
composer require phattarachai/mail-log-laravel
php artisan mail-log:install
```

`mail-log:install` is idempotent. It publishes the package config, the Spatie `laravel-medialibrary` migration (auto-detected when the `media` table is missing), and the package's own migrations. It then writes three env keys (`MAIL_LOG_ENABLED`, `MAIL_LOG_RETENTION_DAYS`, `MAIL_LOG_UI_PATH`) only when absent, and prints the snippets you need to paste into `AppServiceProvider::boot()`, a Mailable, and the scheduler.

Re-run safely. Pass `--dry-run` to print intended changes without writing.

After install, paste the auth gate snippet:

```php
use Phattarachai\MailLogLaravel\MailLog;
use Phattarachai\MailLogLaravel\Models\MailLogGroup;

MailLogGroup::registerMorphMap();
MailLog::auth(fn ($request) => $request->user()?->isAdmin() ?? false);
```

The default policy blocks `/mail-log` unless `APP_DEBUG=true` — loud, safe failure until you opt in.

## Per-Mailable opt-in

Drop the trait into a Mailable and tell it what model originated the send:

```php
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Headers;
use Phattarachai\MailLogLaravel\Concerns\HasMailLog;

class OrderShippedMail extends Mailable
{
    use HasMailLog;

    public function __construct(public Order $order) {}

    protected function mailLogModel(): ?\Illuminate\Database\Eloquent\Model
    {
        return $this->order;
    }

    public function headers(): Headers
    {
        return $this->withMailLog(new Headers());
    }
}
```

Now every `Mail::to($recipient)->send(new OrderShippedMail($order))` to ANY recipient lands in the same group; sending the same Mailable for a different `Order` creates a separate group.

### Trait hooks

All return safe defaults — override only what you need.

| Hook | Returns | Behavior |
|---|---|---|
| `mailLogModel(): ?Model` | `null` | The originating record. Folded into the fingerprint when mode includes `'model'` (default). |
| `mailLogNotificationClass(): ?string` | `null` | The source `Notification` when the Mailable is constructed inside `toMail()`. Lets you fingerprint on the notification instead. |
| `mailLogFingerprintHints(): array` | `[]` | Extra strings (tenant id, A/B variant) folded in when mode includes `'hints'`. |
| `mailLogFingerprintMode(): ?array` | `null` | Per-Mailable override of `['class', 'model']`. Valid: `class`, `notification_class`, `model`, `hints`, `subject`, `body`, `mailer`. |
| `mailLogSkip(): bool` | `false` | Opt this Mailable out of capture entirely (health-check pings, transactional one-offs). |

## Group / event model

Two tables in lock-step:

```
mail_log_groups (one row per fingerprint)
├── fingerprint  (CHAR 64, UNIQUE — the dedup key)
├── subject / from / mailable_class / notification_class / mailer  (latest seen)
├── model_type / model_id  (morphTo)
├── html_body / text_body  (first-send wins; representative preview only)
├── sent_count / failed_count / latest_status  (denormalized counters)
└── created_at / updated_at

mail_logs (one row per Mail::send(), append-only)
├── group_id  (FK → mail_log_groups.id, cascadeOnDelete)
├── to / cc / bcc  (JSON)
├── status  (Pending → Sent | Failed)
├── error_message / seconds / sent_at
└── created_at
```

The body shown on the show page is the **first send** in the group — per-recipient body variation (signed URLs, magic-login tokens, personalized greetings) is NOT captured per row. The package favors fingerprint stability over body fidelity in v0.1; per-event body storage is on the v0.2 roadmap behind `MAIL_LOG_STORE_EVENT_BODIES=true`.

## Config

```env
MAIL_LOG_ENABLED=true                    # master switch — false skips listener registration
MAIL_LOG_RETENTION_DAYS=365              # prunable; null = never prune; events cascade with the group
MAIL_LOG_UI_PATH=mail-log                # URL prefix; null skips route registration
MAIL_LOG_TEST_SEND_ENABLED=true          # show the Test-send button + POST /test-send route
MAIL_LOG_MAX_RECIPIENTS_PER_EVENT=200    # cap recipients stored in a single event row's JSON columns
```

See [`config/mail-log.php`](config/mail-log.php) for the full schema; the [Boost skill `reference.md`](resources/boost/skills/mail-log/reference.md) walks every key.

## Scheduling retention

```php
// bootstrap/app.php (Laravel 11+) — inside ->withSchedule(function (Schedule $schedule) { ... }):
$schedule->command('model:prune', [
    '--model' => [\Phattarachai\MailLogLaravel\Models\MailLogGroup::class],
])->daily();
```

Events cascade-delete via FK when their parent group is pruned.

## Requirements

- PHP `^8.4`
- Laravel `^12` or `^13`
- `spatie/laravel-medialibrary ^11`

## Credits

Built by [Phattarachai Chaimongkol](https://github.com/phattarachai). Same shipping pattern as Laravel [Horizon](https://laravel.com/docs/horizon) and [Pulse](https://laravel.com/docs/pulse) — pre-built CSS + JS committed to `dist/`, inlined into the layout via `MailLog::css()` / `MailLog::js()` helpers, no host-side `npm install` required.

## License

MIT. See [LICENSE](LICENSE.md).
