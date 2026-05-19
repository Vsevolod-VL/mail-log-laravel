<?php

declare(strict_types=1);

namespace Phattarachai\MailLogLaravel\Listeners;

use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Queue\Events\JobFailed;

/**
 * Outgoing-mail listener. Phase 3 fleshes out the firstOrCreate group /
 * append-event / transactional counter flow described in 1-overview.md.
 *
 * Phase 1 wires the bindings into the service provider so the registration
 * path is covered by ServiceProviderTest — the handlers are intentional
 * no-ops until Phase 3 lands.
 */
class LogOutgoingMail
{
    public function handleSending(MessageSending $event): ?bool
    {
        return null;
    }

    public function handleSent(MessageSent $event): void
    {
        //
    }

    public function handleFailed(JobFailed $event): void
    {
        //
    }
}
