<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Phattarachai\MailLogLaravel\Enums\MailLogStatus;
use Phattarachai\MailLogLaravel\Models\MailLog;
use Phattarachai\MailLogLaravel\Models\MailLogGroup;

it('creates a row from the factory with every expected column', function (): void {
    $event = MailLog::factory()->create();

    expect($event->exists)->toBeTrue()
        ->and($event->group_id)->toBeInt()
        ->and($event->to)->toBeArray()
        ->and($event->status)->toBe(MailLogStatus::Sent);

    expect(Schema::hasColumns($event->getTable(), [
        'id', 'group_id', 'to', 'cc', 'bcc', 'status',
        'error_message', 'seconds', 'sent_at', 'created_at',
    ]))->toBeTrue();
});

it('does not include updated_at on the events table or model', function (): void {
    $event = MailLog::factory()->create();

    expect(Schema::hasColumn($event->getTable(), 'updated_at'))->toBeFalse()
        ->and($event->getUpdatedAtColumn())->toBeNull();
});

it('belongs to its parent group', function (): void {
    $group = MailLogGroup::factory()->create();
    $event = MailLog::factory()->for($group, 'group')->create();

    expect($event->group)->toBeInstanceOf(MailLogGroup::class)
        ->and($event->group->id)->toBe($group->id);
});

it('exposes failed and pending factory states', function (): void {
    expect(MailLog::factory()->pending()->create())
        ->status->toBe(MailLogStatus::Pending)
        ->sent_at->toBeNull()
        ->seconds->toBeNull();

    expect(MailLog::factory()->failed()->create())
        ->status->toBe(MailLogStatus::Failed)
        ->error_message->not->toBeEmpty();
});

it('casts the recipient columns as arrays', function (): void {
    $event = MailLog::factory()->create([
        'to' => ['a@example.com', 'b@example.com'],
        'cc' => ['c@example.com'],
        'bcc' => null,
    ]);

    expect($event->to)->toBe(['a@example.com', 'b@example.com'])
        ->and($event->cc)->toBe(['c@example.com'])
        ->and($event->bcc)->toBeNull();
});
