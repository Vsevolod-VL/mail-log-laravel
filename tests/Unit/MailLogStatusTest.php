<?php

declare(strict_types=1);

use Phattarachai\MailLogLaravel\Enums\MailLogStatus;

it('exposes pending / sent / failed cases', function (): void {
    expect(MailLogStatus::Pending->value)->toBe('pending')
        ->and(MailLogStatus::Sent->value)->toBe('sent')
        ->and(MailLogStatus::Failed->value)->toBe('failed');
});

it('returns a human label for each case', function (): void {
    expect(MailLogStatus::Pending->getLabel())->toBe('Pending')
        ->and(MailLogStatus::Sent->getLabel())->toBe('Sent')
        ->and(MailLogStatus::Failed->getLabel())->toBe('Failed');
});

it('returns a tailwind color stem per case', function (): void {
    expect(MailLogStatus::Pending->getColor())->toBe('amber')
        ->and(MailLogStatus::Sent->getColor())->toBe('emerald')
        ->and(MailLogStatus::Failed->getColor())->toBe('rose');
});
