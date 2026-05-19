<?php

declare(strict_types=1);

namespace Phattarachai\MailLogLaravel\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Phattarachai\MailLogLaravel\Enums\MailLogStatus;
use Phattarachai\MailLogLaravel\Models\MailLogGroup;

/**
 * @extends Factory<MailLogGroup>
 */
class MailLogGroupFactory extends Factory
{
    protected $model = MailLogGroup::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'fingerprint' => bin2hex(random_bytes(32)),
            'subject' => fake()->sentence(),
            'from' => fake()->safeEmail(),
            'mailable_class' => null,
            'notification_class' => null,
            'model_type' => null,
            'model_id' => null,
            'mailer' => 'smtp',
            'html_body' => '<p>'.fake()->paragraph().'</p>',
            'text_body' => fake()->paragraph(),
            'sent_count' => 1,
            'failed_count' => 0,
            'latest_status' => MailLogStatus::Sent,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'sent_count' => 0,
            'failed_count' => 0,
            'latest_status' => MailLogStatus::Pending,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'sent_count' => 0,
            'failed_count' => 1,
            'latest_status' => MailLogStatus::Failed,
        ]);
    }

    public function merged(): static
    {
        return $this->state(fn () => [
            'sent_count' => 5,
            'failed_count' => 0,
            'latest_status' => MailLogStatus::Sent,
        ]);
    }

    public function withFailures(): static
    {
        return $this->state(fn () => [
            'sent_count' => 4,
            'failed_count' => 1,
            'latest_status' => MailLogStatus::Sent,
        ]);
    }
}
