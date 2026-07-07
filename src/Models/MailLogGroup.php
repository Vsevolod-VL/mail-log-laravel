<?php

declare(strict_types=1);

namespace Phattarachai\MailLogLaravel\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Override;
use Phattarachai\MailLogLaravel\Database\Factories\MailLogGroupFactory;
use Phattarachai\MailLogLaravel\Enums\MailLogStatus;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class MailLogGroup extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;
    use Prunable;

    protected $guarded = [];

    #[Override]
    public function getTable(): string
    {
        return (string) config('mail-log.tables.groups', 'mail_log_groups');
    }

    /**
     * @return HasMany<MailLog, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(MailLog::class, 'group_id')->latest();
    }

    /**
     * @return HasOne<MailLog, $this>
     */
    public function latestEvent(): HasOne
    {
        return $this->hasOne(MailLog::class, 'group_id')->ofMany('created_at', 'max');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Apply a finished event to this group: bump the right counter, set
     * latest_status, touch updated_at. Wrapped in a transaction so the
     * status change and counter increment commit together.
     */
    public function recordEvent(MailLog $event): void
    {
        DB::transaction(function () use ($event): void {
            $this->latest_status = $event->status;
            $this->save();

            match ($event->status) {
                MailLogStatus::Sent => $this->increment('sent_count'),
                MailLogStatus::Failed => $this->increment('failed_count'),
                default => null,
            };
        });
    }

    /**
     * Ratio of sent events to (sent + failed). 1.0 when no outcomes recorded.
     */
    public function successRate(): float
    {
        $total = $this->sent_count + $this->failed_count;

        if ($total === 0) {
            return 1.0;
        }

        return $this->sent_count / $total;
    }

    /**
     * Distinct recipients aggregated across every event in this group.
     */
    public function uniqueRecipients(): Collection
    {
        return $this->events()
            ->get(['to'])
            ->pluck('to')
            ->flatten(1)
            ->unique()
            ->values();
    }

    /**
     * Prunable scope — groups idle past the configured retention window.
     * Events cascade-delete via FK when a group is pruned.
     */
    public function prunable(): Builder
    {
        $days = config('mail-log.retention_days');

        if ($days === null) {
            return static::query()->whereRaw('1 = 0');
        }

        return static::query()->where('updated_at', '<', now()->subDays((int) $days));
    }

    public function registerMediaCollections(): void
    {
        $collection = $this->addMediaCollection(
            (string) config('mail-log.attachments.collection', 'attachments')
        );

        $disk = config('mail-log.attachments.disk');

        if ($disk !== null) {
            $collection->useDisk((string) $disk);
        }
    }

    /**
     * Register a stable morph alias so model() doesn't bake the
     * package's fully-qualified class name into host data.
     */
    public static function registerMorphMap(): void
    {
        Relation::morphMap([
            (string) config('mail-log.morph_alias', 'mail_log_group') => static::class,
        ]);
    }

    #[Override]
    protected function casts(): array
    {
        return [
            'latest_status' => MailLogStatus::class,
            'sent_count' => 'integer',
            'failed_count' => 'integer',
        ];
    }

    protected static function newFactory(): Factory
    {
        return MailLogGroupFactory::new();
    }
}
