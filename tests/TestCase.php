<?php

declare(strict_types=1);

namespace Phattarachai\MailLogLaravel\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Phattarachai\MailLogLaravel\MailLog;
use Phattarachai\MailLogLaravel\MailLogServiceProvider;
use Spatie\MediaLibrary\MediaLibraryServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        MailLog::flushAuth();
    }

    /**
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            MediaLibraryServiceProvider::class,
            MailLogServiceProvider::class,
        ];
    }

    /**
     * @param  \Illuminate\Foundation\Application  $app
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.debug', false);
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('mail-log.enabled', true);
        $app['config']->set('mail-log.ui.path', 'mail-log');
    }
}
