<?php

declare(strict_types=1);

namespace Phattarachai\MailLogLaravel\Listeners;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Mail\SendQueuedMailable;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Phattarachai\MailLogLaravel\Enums\MailLogStatus;
use Phattarachai\MailLogLaravel\Models\MailLog;
use Phattarachai\MailLogLaravel\Models\MailLogGroup;
use Phattarachai\MailLogLaravel\Support\Fingerprinter;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;
use Throwable;

class LogOutgoingMail
{
    public function __construct(private readonly Fingerprinter $fingerprinter) {}

    public function handleSending(MessageSending $event): ?bool
    {
        if (! config('mail-log.enabled', true)) {
            return null;
        }

        $message = $event->message;

        if ($this->headerValue($message, 'X-Mail-Log-Skip') === '1') {
            return null;
        }

        $mailer = $event->data['mailer'] ?? null;

        try {
            $fingerprint = ($this->fingerprinter)($message, $mailer);

            $eventRow = DB::transaction(function () use ($message, $fingerprint, $mailer): MailLog {
                $group = $this->resolveGroup($fingerprint, $message, $mailer);

                return MailLog::create([
                    'group_id' => $group->id,
                    'to' => $this->extractAddresses($message->getTo()),
                    'cc' => $this->extractAddresses($message->getCc()) ?: null,
                    'bcc' => $this->extractAddresses($message->getBcc()) ?: null,
                    'status' => MailLogStatus::Pending,
                ]);
            });

            $headers = $message->getHeaders();
            $headers->addTextHeader('X-Mail-Log-Event-Id', (string) $eventRow->id);
            $headers->addTextHeader('X-Mail-Log-Start', (string) microtime(true));
        } catch (Throwable $e) {
            report($e);
        }

        return null;
    }

    public function handleSent(MessageSent $event): void
    {
        try {
            $message = $event->message;
            $eventId = $this->headerValue($message, 'X-Mail-Log-Event-Id');

            if ($eventId === null) {
                return;
            }

            $row = MailLog::find($eventId);

            if ($row === null) {
                return;
            }

            DB::transaction(function () use ($row, $message): void {
                $row->update([
                    'status' => MailLogStatus::Sent,
                    'sent_at' => now(),
                    'seconds' => $this->calculatedSeconds($message),
                ]);

                $row->group?->recordEvent($row);
            });
        } catch (Throwable $e) {
            report($e);
        }
    }

    public function handleFailed(JobFailed $event): void
    {
        $payload = $event->job->payload();

        if (($payload['data']['commandName'] ?? null) !== SendQueuedMailable::class) {
            return;
        }

        $eventId = null;
        $mailableClass = null;
        $modelType = null;
        $modelId = null;

        try {
            /** @var SendQueuedMailable $command */
            $command = unserialize($payload['data']['command']);
            $headers = $command->mailable->headers();
            $eventId = $headers->text['X-Mail-Log-Event-Id'] ?? null;
            $mailableClass = $headers->text['X-Mail-Class'] ?? null;
            $modelType = $headers->text['X-Mail-Model-Type'] ?? null;
            $modelId = $headers->text['X-Mail-Model-Id'] ?? null;
        } catch (Throwable $e) {
            report($e);
        }

        $row = $this->resolveFailedEvent($eventId, $mailableClass, $modelType, $modelId);

        if ($row === null) {
            return;
        }

        try {
            DB::transaction(function () use ($row, $event): void {
                $row->update([
                    'status' => MailLogStatus::Failed,
                    'error_message' => Str::limit($event->exception->getMessage(), 500),
                ]);

                $row->group?->recordEvent($row);
            });
        } catch (Throwable $e) {
            report($e);
        }
    }

    private function resolveFailedEvent(
        ?string $eventId,
        ?string $mailableClass,
        ?string $modelType,
        ?string $modelId,
    ): ?MailLog {
        if ($eventId !== null) {
            $row = MailLog::find($eventId);

            if ($row !== null) {
                return $row;
            }
        }

        if ($mailableClass === null || $modelType === null || $modelId === null) {
            return null;
        }

        return MailLog::query()
            ->where('status', MailLogStatus::Pending)
            ->whereHas('group', function ($query) use ($mailableClass, $modelType, $modelId): void {
                $query->where('mailable_class', $mailableClass)
                    ->where('model_type', $modelType)
                    ->where('model_id', $modelId);
            })
            ->latest('created_at')
            ->first();
    }

    private function resolveGroup(string $fingerprint, Email $message, ?string $mailer): MailLogGroup
    {
        $canonical = $this->canonicalAttributes($message, $mailer);

        try {
            $group = MailLogGroup::firstOrCreate(
                ['fingerprint' => $fingerprint],
                $canonical + [
                    'html_body' => $message->getHtmlBody(),
                    'text_body' => $message->getTextBody(),
                    'latest_status' => MailLogStatus::Pending,
                ],
            );
        } catch (UniqueConstraintViolationException) {
            $group = MailLogGroup::where('fingerprint', $fingerprint)->firstOrFail();
        }

        if ($group->wasRecentlyCreated) {
            $this->storeAttachments($group, $message);

            return $group;
        }

        $group->forceFill($canonical)->save();

        return $group;
    }

    /**
     * @return array<string, string|null>
     */
    private function canonicalAttributes(Email $message, ?string $mailer): array
    {
        return [
            'subject' => $message->getSubject(),
            'from' => $this->formatAddresses($message->getFrom()),
            'mailable_class' => $this->headerValue($message, 'X-Mail-Class'),
            'notification_class' => $this->headerValue($message, 'X-Mail-Notification-Class'),
            'model_type' => $this->headerValue($message, 'X-Mail-Model-Type'),
            'model_id' => $this->headerValue($message, 'X-Mail-Model-Id'),
            'mailer' => $mailer,
        ];
    }

    private function headerValue(Email $message, string $name): ?string
    {
        $header = $message->getHeaders()->get($name);

        if ($header === null) {
            return null;
        }

        $value = $header->getBodyAsString();

        return $value === '' ? null : $value;
    }

    /**
     * @param  Address[]  $addresses
     * @return string[]
     */
    private function extractAddresses(array $addresses): array
    {
        $cap = (int) config('mail-log.fingerprint.max_recipients_per_event', 200);

        $formatted = array_map(
            fn (Address $address): string => $address->getName() !== ''
                ? "{$address->getName()} <{$address->getAddress()}>"
                : $address->getAddress(),
            $addresses,
        );

        return array_slice($formatted, 0, $cap);
    }

    /**
     * @param  Address[]  $addresses
     */
    private function formatAddresses(array $addresses): ?string
    {
        if ($addresses === []) {
            return null;
        }

        return implode(', ', $this->extractAddresses($addresses));
    }

    private function storeAttachments(MailLogGroup $group, Email $message): void
    {
        $maxBytes = (int) config('mail-log.attachments.max_bytes_each', 10 * 1024 * 1024);
        $collection = (string) config('mail-log.attachments.collection', 'attachments');

        foreach ($message->getAttachments() as $attachment) {
            if (! $attachment instanceof DataPart) {
                continue;
            }

            $body = $attachment->getBody();

            if (strlen($body) > $maxBytes) {
                continue;
            }

            $filename = $attachment->getFilename() ?? 'attachment';
            $tempPath = tempnam(sys_get_temp_dir(), 'mail_attachment_');
            file_put_contents($tempPath, $body);

            $group->addMedia($tempPath)
                ->usingFileName($filename)
                ->toMediaCollection($collection);
        }
    }

    private function calculatedSeconds(Email $message): ?float
    {
        $start = $this->headerValue($message, 'X-Mail-Log-Start');

        if ($start === null) {
            return null;
        }

        return round(microtime(true) - (float) $start, 3);
    }
}
