<?php

declare(strict_types=1);

namespace VsevolodVL\MailLogLaravel\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use VsevolodVL\MailLogLaravel\Enums\MailLogStatus;
use VsevolodVL\MailLogLaravel\Models\MailLog;
use VsevolodVL\MailLogLaravel\Models\MailLogGroup;

/**
 * @extends Factory<MailLog>
 */
class MailLogFactory extends Factory
{
    protected $model = MailLog::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'group_id' => MailLogGroup::factory(),
            'to' => [fake()->safeEmail()],
            'cc' => null,
            'bcc' => null,
            'status' => MailLogStatus::Sent,
            'error_message' => null,
            'seconds' => fake()->randomFloat(3, 0.05, 2.5),
            'sent_at' => now(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => MailLogStatus::Pending,
            'sent_at' => null,
            'seconds' => null,
            'error_message' => null,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => MailLogStatus::Failed,
            'sent_at' => now(),
            'error_message' => fake()->sentence(),
        ]);
    }
}
