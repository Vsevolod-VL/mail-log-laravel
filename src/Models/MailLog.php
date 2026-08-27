<?php

declare(strict_types=1);

namespace VsevolodVL\MailLogLaravel\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use VsevolodVL\MailLogLaravel\Enums\MailLogStatus;
use VsevolodVL\MailLogLaravel\Database\Factories\MailLogFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MailLog extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $guarded = [];

    public function getTable(): string
    {
        return (string) config('mail-log.tables.events', 'mail_logs');
    }

    protected function casts(): array
    {
        return [
            'to' => 'array',
            'cc' => 'array',
            'bcc' => 'array',
            'status' => MailLogStatus::class,
            'sent_at' => 'datetime',
            'seconds' => 'float',
        ];
    }

    /**
     * Append-only outcome records have no updated_at.
     */
    public function getUpdatedAtColumn(): ?string
    {
        return null;
    }

    /**
     * @return BelongsTo<MailLogGroup, $this>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(MailLogGroup::class, 'group_id');
    }

    protected static function newFactory(): Factory
    {
        return MailLogFactory::new();
    }
}

