<?php

declare(strict_types=1);

namespace Phattarachai\MailLogLaravel\Contracts;

/**
 * Optional contract for Eloquent models attached to a MailLogGroup. Implement
 * on the model to give the dashboard a deep-link back into the host app.
 *
 * Phase 4b's group detail page calls these when the linked model honors the
 * contract — otherwise the model column renders as plain text.
 */
interface MailLogLinkable
{
    /**
     * Short, human-readable label for the model (e.g. order number, user name).
     */
    public function mailLogTitle(): string;

    /**
     * URL pointing back to the model's canonical detail page in the host app.
     */
    public function mailLogUrl(): ?string;
}
