<?php

declare(strict_types=1);

namespace VsevolodVL\MailLogLaravel\Enums;

enum MailLogStatus: string
{
    case Pending = 'pending';
    case Sent = 'sent';
    case Failed = 'failed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Sent => 'Sent',
            self::Failed => 'Failed',
        };
    }

    /**
     * Tailwind color stem — UI partials compose the full class
     * (e.g. `bg-{stem}-50 text-{stem}-700`).
     */
    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Sent => 'emerald',
            self::Failed => 'rose',
        };
    }
}

