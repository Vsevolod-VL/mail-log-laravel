<?php

declare(strict_types=1);

namespace VsevolodVL\MailLogLaravel\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use VsevolodVL\MailLogLaravel\Concerns\HasMailLog;

class TestMail extends Mailable
{
    use HasMailLog;

    /**
     * @param  array<int, array{path: string, name: string, mime: ?string}>  $files
     */
    public function __construct(
        public string $message = 'This is a test message dispatched from the Mail Log dashboard.',
        public array $files = [],
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Mail Log test send',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail-log::mail.test',
            with: ['body' => $this->message],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return array_map(
            fn (array $file): Attachment => Attachment::fromPath($file['path'])
                ->as($file['name'])
                ->withMime($file['mime'] ?? 'application/octet-stream'),
            $this->files,
        );
    }

    public function headers(): Headers
    {
        return $this->withMailLog(new Headers);
    }
}
