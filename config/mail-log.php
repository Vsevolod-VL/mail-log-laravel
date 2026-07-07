<?php

declare(strict_types=1);
use Phattarachai\MailLogLaravel\Http\Middleware\Authorize;

return [
    'enabled' => env('MAIL_LOG_ENABLED', default: true),

    'tables' => [
        'groups' => env('MAIL_LOG_TABLE_GROUPS', 'mail_log_groups'),
        'events' => env('MAIL_LOG_TABLE_EVENTS', 'mail_logs'),
    ],

    // Prunable retention applied to GROUPS keyed on updated_at — events cascade-delete via FK.
    // null = never prune.
    'retention_days' => env('MAIL_LOG_RETENTION_DAYS', 365),

    'attachments' => [
        'disk' => env('MAIL_LOG_ATTACHMENT_DISK'),
        'collection' => 'attachments',
        'max_bytes_each' => (int) env('MAIL_LOG_ATTACHMENT_MAX_BYTES', 10 * 1024 * 1024),
    ],

    'fingerprint' => [
        // Inputs included in the fingerprint when no per-mailable override is set.
        // Order doesn't matter — Fingerprinter sorts before hashing.
        // Values: 'class' | 'notification_class' | 'model' | 'hints' | 'subject' | 'body' | 'mailer'.
        'default_mode' => ['class', 'model'],

        // Regex patterns applied to body BEFORE hashing — only relevant on the
        // raw-mail fallback path (Mail::raw / Mail::send('view.name', ...) without a class).
        'body_strip_patterns' => [
            '/([?&])(token|signature|expires|_token)=[^&"\'\s<>]+/i',
            '/\/verify-email\/\d+\/[A-Za-z0-9]+/i',
            '/\/password\/reset\/[A-Za-z0-9]+/i',
            '/\b\d{4}-\d{2}-\d{2}[T\s]\d{2}:\d{2}(:\d{2})?(?:\.\d+)?(?:Z|[+-]\d{2}:?\d{2})?\b/',
        ],

        // Cap on recipients stored in a single event row's to/cc/bcc — protects
        // against malformed mailables blowing up the json column.
        'max_recipients_per_event' => (int) env('MAIL_LOG_MAX_RECIPIENTS_PER_EVENT', 200),
    ],

    'ui' => [
        'path' => env('MAIL_LOG_UI_PATH', 'mail-log'),
        'middleware' => ['web', Authorize::class],
        'auth_default' => 'debug-only',
        'page_size' => (int) env('MAIL_LOG_UI_PAGE_SIZE', 25),
        'brand' => env('MAIL_LOG_UI_BRAND', 'Mail Log'),

        // Link back to the host application (rendered top-left of the header).
        //   null / unset → defaults to url('/') (host app home).
        //   false        → hides the link entirely.
        //   string       → uses that URL verbatim.
        'back_link' => [
            'url' => env('MAIL_LOG_UI_BACK_URL'),
            'label' => env('MAIL_LOG_UI_BACK_LABEL', 'Back to app'),
        ],
    ],

    'morph_alias' => env('MAIL_LOG_MORPH_ALIAS', 'mail_log_group'),

    'test_send' => [
        'enabled' => filter_var(env('MAIL_LOG_TEST_SEND_ENABLED', default: true), FILTER_VALIDATE_BOOL),
    ],
];
