<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Headers;
use Phattarachai\MailLogLaravel\Concerns\HasMailLog;
use Phattarachai\MailLogLaravel\Models\MailLogGroup;

beforeEach(function () {
    Relation::morphMap([], false);
});

it('stamps the X-Mail-Class header with the Mailable FQCN', function () {
    $mail = new BareMailable;

    $headers = $mail->withMailLog(new Headers);

    expect($headers->text['X-Mail-Class'])->toBe(BareMailable::class);
});

it('stamps the X-Mail-Model-Type and X-Mail-Model-Id headers when a model is set', function () {
    MailLogGroup::registerMorphMap();
    $model = MailLogGroup::factory()->create();

    $mail = new MailableWithModel($model);
    $headers = $mail->withMailLog(new Headers);

    expect($headers->text['X-Mail-Model-Type'])->toBe('mail_log_group')
        ->and($headers->text['X-Mail-Model-Id'])->toBe((string) $model->getKey());
});

it('omits the model headers when no model is returned', function () {
    $headers = (new BareMailable)->withMailLog(new Headers);

    expect($headers->text)->not->toHaveKey('X-Mail-Model-Type')
        ->and($headers->text)->not->toHaveKey('X-Mail-Model-Id');
});

it('stamps the hint header as JSON when hints are provided', function () {
    $mail = new MailableWithHints(['tenant_id' => 7, 'role' => 'admin']);

    $headers = $mail->withMailLog(new Headers);

    expect($headers->text)->toHaveKey('X-Mail-Fingerprint-Hint');
    expect(json_decode($headers->text['X-Mail-Fingerprint-Hint'], true))
        ->toBe(['tenant_id' => 7, 'role' => 'admin']);
});

it('omits the hint header when hints are empty', function () {
    $headers = (new BareMailable)->withMailLog(new Headers);

    expect($headers->text)->not->toHaveKey('X-Mail-Fingerprint-Hint');
});

it('stamps the X-Mail-Fingerprint-Mode header when the override returns a mode list', function () {
    $mail = new MailableWithModeOverride(['class', 'model', 'hints']);

    $headers = $mail->withMailLog(new Headers);

    expect($headers->text)->toHaveKey('X-Mail-Fingerprint-Mode');
    expect(json_decode($headers->text['X-Mail-Fingerprint-Mode'], true))
        ->toBe(['class', 'model', 'hints']);
});

it('omits the mode header when the trait returns the null default (config default applies)', function () {
    $headers = (new BareMailable)->withMailLog(new Headers);

    expect($headers->text)->not->toHaveKey('X-Mail-Fingerprint-Mode');
});

it('stamps the X-Mail-Log-Skip header when mailLogSkip is true', function () {
    $headers = (new SkippedMailable)->withMailLog(new Headers);

    expect($headers->text['X-Mail-Log-Skip'])->toBe('1');
});

it('stamps the notification class header when the host returns one', function () {
    $headers = (new NotificationDrivenMailable)->withMailLog(new Headers);

    expect($headers->text['X-Mail-Notification-Class'])->toBe('App\\Notifications\\OrderShipped');
});

it('merges into rather than overwrites existing headers text array', function () {
    $headers = new Headers;
    $headers->text = ['X-Existing' => 'preserve-me'];

    $merged = (new BareMailable)->withMailLog($headers);

    expect($merged->text['X-Existing'])->toBe('preserve-me')
        ->and($merged->text['X-Mail-Class'])->toBe(BareMailable::class);
});

class BareMailable extends Mailable
{
    use HasMailLog;
}

class MailableWithModel extends Mailable
{
    use HasMailLog;

    public function __construct(public Model $model) {}

    protected function mailLogModel(): ?Model
    {
        return $this->model;
    }
}

class MailableWithHints extends Mailable
{
    use HasMailLog;

    /**
     * @param  array<string, scalar>  $hints
     */
    public function __construct(public array $hints) {}

    protected function mailLogFingerprintHints(): array
    {
        return $this->hints;
    }
}

class MailableWithModeOverride extends Mailable
{
    use HasMailLog;

    /**
     * @param  array<int, string>  $mode
     */
    public function __construct(public array $mode) {}

    protected function mailLogFingerprintMode(): ?array
    {
        return $this->mode;
    }
}

class SkippedMailable extends Mailable
{
    use HasMailLog;

    protected function mailLogSkip(): bool
    {
        return true;
    }
}

class NotificationDrivenMailable extends Mailable
{
    use HasMailLog;

    protected function mailLogNotificationClass(): ?string
    {
        return 'App\\Notifications\\OrderShipped';
    }
}
