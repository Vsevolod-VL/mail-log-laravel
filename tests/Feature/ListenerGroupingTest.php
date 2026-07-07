<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Phattarachai\MailLogLaravel\Concerns\HasMailLog;
use Phattarachai\MailLogLaravel\Enums\MailLogStatus;
use Phattarachai\MailLogLaravel\Models\MailLog;
use Phattarachai\MailLogLaravel\Models\MailLogGroup;

beforeEach(function (): void {
    Relation::morphMap([], merge: false);
    MailLogGroup::registerMorphMap();

    config()->set('mail.default', 'array');
    config()->set('mail.mailers.array', ['transport' => 'array']);
    config()->set('mail.from', ['address' => 'app@example.com', 'name' => 'App']);
});

it('groups three sends of the same mailable + model into one group with three events', function (): void {
    $order = MailLogGroup::factory()->create();

    Mail::to('a@example.com')->send(new ListenerOrderMail($order));
    Mail::to('b@example.com')->send(new ListenerOrderMail($order));
    Mail::to('c@example.com')->send(new ListenerOrderMail($order));

    expect(MailLogGroup::query()->where('id', '!=', $order->id)->count())->toBe(1);

    $group = MailLogGroup::query()->where('id', '!=', $order->id)->first();

    expect($group->mailable_class)->toBe(ListenerOrderMail::class)
        ->and($group->model_type)->toBe('mail_log_group')
        ->and((string) $group->model_id)->toBe((string) $order->id)
        ->and($group->sent_count)->toBe(3)
        ->and($group->failed_count)->toBe(0)
        ->and($group->latest_status)->toBe(MailLogStatus::Sent);

    $recipients = MailLog::query()
        ->where('group_id', $group->id)
        ->pluck('to')
        ->map(fn ($row) => $row[0])
        ->sort()
        ->values()
        ->all();

    expect($recipients)->toBe(['a@example.com', 'b@example.com', 'c@example.com']);
});

it('creates separate groups for the same mailable bound to different models', function (): void {
    $order1 = MailLogGroup::factory()->create();
    $order2 = MailLogGroup::factory()->create();

    Mail::to('a@example.com')->send(new ListenerOrderMail($order1));
    Mail::to('b@example.com')->send(new ListenerOrderMail($order2));

    $created = MailLogGroup::query()
        ->whereNotIn('id', [$order1->id, $order2->id])
        ->get();

    expect($created)->toHaveCount(2);

    $modelIds = $created->pluck('model_id')->map(fn ($id) => (string) $id)->sort()->values()->all();
    expect($modelIds)->toBe([(string) $order1->id, (string) $order2->id]);
});

it('handles two parallel sending events with the same fingerprint as one group + two events', function (): void {
    $order = MailLogGroup::factory()->create();

    Mail::to('a@example.com')->send(new ListenerOrderMail($order));
    Mail::to('b@example.com')->send(new ListenerOrderMail($order));

    $group = MailLogGroup::query()->where('id', '!=', $order->id)->first();

    expect(MailLog::query()->where('group_id', $group->id)->count())->toBe(2)
        ->and($group->sent_count)->toBe(2);
});

it('stores attachments only on the first event in a group', function (): void {
    config()->set('mail-log.attachments.disk', 'public');
    Storage::fake('public');

    $order = MailLogGroup::factory()->create();

    Mail::to('a@example.com')->send(new ListenerOrderMailWithAttachment($order));
    Mail::to('b@example.com')->send(new ListenerOrderMailWithAttachment($order));

    $group = MailLogGroup::query()
        ->where('id', '!=', $order->id)
        ->where('mailable_class', ListenerOrderMailWithAttachment::class)
        ->firstOrFail();

    expect($group->getMedia('attachments'))->toHaveCount(1)
        ->and(MailLog::query()->where('group_id', $group->id)->count())->toBe(2);
});

it('skips logging entirely when the trait reports mailLogSkip = true', function (): void {
    $order = MailLogGroup::factory()->create();

    Mail::to('a@example.com')->send(new ListenerSkippedMail($order));

    expect(MailLogGroup::query()->where('id', '!=', $order->id)->count())->toBe(0)
        ->and(MailLog::query()->count())->toBe(0);
});

it('captures raw Mail::raw sends via the body-hash fallback path', function (): void {
    Mail::raw('Hello from the raw path', function ($message): void {
        $message->to('raw@example.com')->subject('Raw daily digest');
    });

    $group = MailLogGroup::query()->firstOrFail();

    expect($group->mailable_class)->toBeNull()
        ->and($group->subject)->toBe('Raw daily digest')
        ->and($group->sent_count)->toBe(1)
        ->and($group->latest_status)->toBe(MailLogStatus::Sent);

    $event = MailLog::query()->firstOrFail();
    expect($event->to)->toBe(['raw@example.com']);
});

class ListenerOrderMail extends Mailable
{
    use HasMailLog;

    public function __construct(public Model $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Order shipped');
    }

    public function content(): Content
    {
        return new Content(htmlString: '<p>Order shipped</p>');
    }

    public function headers(): Headers
    {
        return $this->withMailLog(new Headers);
    }

    protected function mailLogModel(): ?Model
    {
        return $this->order;
    }
}

class ListenerOrderMailWithAttachment extends Mailable
{
    use HasMailLog;

    public function __construct(public Model $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Order shipped with invoice');
    }

    public function content(): Content
    {
        return new Content(htmlString: '<p>Order shipped with invoice</p>');
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(
                fn () => 'invoice contents',
                'invoice.pdf',
            )->withMime('application/pdf'),
        ];
    }

    public function headers(): Headers
    {
        return $this->withMailLog(new Headers);
    }

    protected function mailLogModel(): ?Model
    {
        return $this->order;
    }
}

class ListenerSkippedMail extends Mailable
{
    use HasMailLog;

    public function __construct(public Model $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Skipped');
    }

    public function content(): Content
    {
        return new Content(htmlString: '<p>Skipped</p>');
    }

    public function headers(): Headers
    {
        return $this->withMailLog(new Headers);
    }

    protected function mailLogModel(): ?Model
    {
        return $this->order;
    }

    protected function mailLogSkip(): bool
    {
        return true;
    }
}
