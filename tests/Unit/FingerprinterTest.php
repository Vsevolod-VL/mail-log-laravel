<?php

declare(strict_types=1);

use Phattarachai\MailLogLaravel\Support\Fingerprinter;
use Symfony\Component\Mime\Email;

/**
 * @param  array<string, string>  $headers
 */
function makeEmail(array $headers = [], string $subject = 'Hello', string $html = '<p>Hi</p>', string $text = 'Hi'): Email
{
    $email = (new Email)
        ->from('app@example.com')
        ->to('user@example.com')
        ->subject($subject)
        ->html($html)
        ->text($text);

    foreach ($headers as $name => $value) {
        $email->getHeaders()->addTextHeader($name, $value);
    }

    return $email;
}

it('produces identical fingerprints for the same class + model', function () {
    $fp = app(Fingerprinter::class);

    $a = makeEmail([
        'X-Mail-Class' => 'App\\Mail\\OrderShipped',
        'X-Mail-Model-Type' => 'order',
        'X-Mail-Model-Id' => '7',
    ]);

    $b = makeEmail([
        'X-Mail-Class' => 'App\\Mail\\OrderShipped',
        'X-Mail-Model-Type' => 'order',
        'X-Mail-Model-Id' => '7',
    ]);

    expect($fp($a))->toBe($fp($b))
        ->and(strlen($fp($a)))->toBe(64);
});

it('keeps the fingerprint stable when only body bytes differ', function () {
    $fp = app(Fingerprinter::class);

    $headers = [
        'X-Mail-Class' => 'App\\Mail\\OrderShipped',
        'X-Mail-Model-Type' => 'order',
        'X-Mail-Model-Id' => '7',
    ];

    $a = makeEmail($headers, html: '<p>Hello Alice — token=abc123</p>', text: 'Hello Alice');
    $b = makeEmail($headers, html: '<p>Hello Bob — token=xyz789</p>', text: 'Hello Bob');

    expect($fp($a))->toBe($fp($b));
});

it('produces different fingerprints for different mailable classes', function () {
    $fp = app(Fingerprinter::class);

    $a = makeEmail([
        'X-Mail-Class' => 'App\\Mail\\OrderShipped',
        'X-Mail-Model-Type' => 'order',
        'X-Mail-Model-Id' => '7',
    ]);

    $b = makeEmail([
        'X-Mail-Class' => 'App\\Mail\\ShipmentDelayed',
        'X-Mail-Model-Type' => 'order',
        'X-Mail-Model-Id' => '7',
    ]);

    expect($fp($a))->not->toBe($fp($b));
});

it('produces different fingerprints for different model ids', function () {
    $fp = app(Fingerprinter::class);

    $a = makeEmail([
        'X-Mail-Class' => 'App\\Mail\\OrderShipped',
        'X-Mail-Model-Type' => 'order',
        'X-Mail-Model-Id' => '7',
    ]);

    $b = makeEmail([
        'X-Mail-Class' => 'App\\Mail\\OrderShipped',
        'X-Mail-Model-Type' => 'order',
        'X-Mail-Model-Id' => '8',
    ]);

    expect($fp($a))->not->toBe($fp($b));
});

it('folds hints into the fingerprint when the mode includes hints', function () {
    $fp = app(Fingerprinter::class);

    $a = makeEmail([
        'X-Mail-Class' => 'App\\Mail\\OrderShipped',
        'X-Mail-Model-Type' => 'order',
        'X-Mail-Model-Id' => '7',
        'X-Mail-Fingerprint-Mode' => json_encode(['class', 'model', 'hints']),
        'X-Mail-Fingerprint-Hint' => json_encode(['tenant_id' => 1]),
    ]);

    $b = makeEmail([
        'X-Mail-Class' => 'App\\Mail\\OrderShipped',
        'X-Mail-Model-Type' => 'order',
        'X-Mail-Model-Id' => '7',
        'X-Mail-Fingerprint-Mode' => json_encode(['class', 'model', 'hints']),
        'X-Mail-Fingerprint-Hint' => json_encode(['tenant_id' => 2]),
    ]);

    expect($fp($a))->not->toBe($fp($b));
});

it('produces identical fingerprints when hint keys arrive in different order', function () {
    $fp = app(Fingerprinter::class);

    $a = makeEmail([
        'X-Mail-Class' => 'App\\Mail\\OrderShipped',
        'X-Mail-Fingerprint-Mode' => json_encode(['class', 'hints']),
        'X-Mail-Fingerprint-Hint' => json_encode(['tenant_id' => 7, 'role' => 'admin']),
    ]);

    $b = makeEmail([
        'X-Mail-Class' => 'App\\Mail\\OrderShipped',
        'X-Mail-Fingerprint-Mode' => json_encode(['class', 'hints']),
        'X-Mail-Fingerprint-Hint' => json_encode(['role' => 'admin', 'tenant_id' => 7]),
    ]);

    expect($fp($a))->toBe($fp($b));
});

it('falls back to subject + mailer + body for raw mail without class headers', function () {
    $fp = app(Fingerprinter::class);

    $a = makeEmail([], subject: 'Daily digest', html: '<p>Same</p>', text: 'Same');
    $b = makeEmail([], subject: 'Daily digest', html: '<p>Same</p>', text: 'Same');

    expect($fp($a, 'smtp'))->toBe($fp($b, 'smtp'));

    $different = makeEmail([], subject: 'Daily digest', html: '<p>Different</p>', text: 'Different');
    expect($fp($a, 'smtp'))->not->toBe($fp($different, 'smtp'));

    $differentMailer = makeEmail([], subject: 'Daily digest', html: '<p>Same</p>', text: 'Same');
    expect($fp($a, 'smtp'))->not->toBe($fp($differentMailer, 'ses'));
});

it('honors an X-Mail-Fingerprint hard override', function () {
    $fp = app(Fingerprinter::class);

    $hex = str_repeat('a', 64);
    $a = makeEmail([
        'X-Mail-Class' => 'App\\Mail\\OrderShipped',
        'X-Mail-Fingerprint' => $hex,
    ]);

    expect($fp($a))->toBe($hex);

    $b = makeEmail([
        'X-Mail-Class' => 'App\\Mail\\OrderShipped',
        'X-Mail-Fingerprint' => 'not-already-hashed',
    ]);

    expect($fp($b))->toBe(hash('sha256', 'not-already-hashed'));
});
