<?php

declare(strict_types=1);

namespace Phattarachai\MailLogLaravel\Support;

use Symfony\Component\Mime\Email;

/**
 * Phase 3 implements the full mode-driven fingerprint algorithm
 * (class+model primary path, body-hash fallback, X-Mail-* header reading).
 *
 * Phase 1 ships the binding so the provider can singleton-register it.
 */
class Fingerprinter
{
    public function __invoke(Email $message, ?string $mailerName = null): string
    {
        return hash('sha256', $message->getSubject().'|'.($mailerName ?? ''));
    }
}
