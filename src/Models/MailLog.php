<?php

declare(strict_types=1);

namespace Phattarachai\MailLogLaravel\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;
use Phattarachai\MailLogLaravel\Database\Factories\MailLogFactory;
use Phattarachai\MailLogLaravel\Enums\MailLogStatus;

class MailLog extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $guarded = [];

    #[Override]
    public function getTable(): string
    {
        return (string) config('mail-log.tables.events', 'mail_logs');
    }

    /**
     * Append-only outcome records have no updated_at.
     */
    #[Override]
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

    #[Override]
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

    protected static function newFactory(): Factory
    {
        return MailLogFactory::new();
    }
}
