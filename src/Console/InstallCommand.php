<?php

declare(strict_types=1);

namespace Phattarachai\MailLogLaravel\Console;

use Illuminate\Console\Command;

/**
 * Phase 5 ports the full EnvWriter-driven flow (config publish + migrate
 * prompt + env block + auth-gate / morph / trait / scheduler snippets).
 *
 * Phase 1 ships the command shell so the service provider can register it
 * via $this->commands([...]) without a class-not-found explosion.
 */
class InstallCommand extends Command
{
    protected $signature = 'mail-log:install {--dry-run : Print intended changes without writing files}';

    protected $description = 'Install Mail Log: publish config + migrations, write env keys, print integration snippets.';

    public function handle(): int
    {
        $this->info('mail-log:install — Phase 1 stub; full installer ships in Phase 5.');

        return self::SUCCESS;
    }
}
