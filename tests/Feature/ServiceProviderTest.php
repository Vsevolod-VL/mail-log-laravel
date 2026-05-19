<?php

declare(strict_types=1);

use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Phattarachai\MailLogLaravel\Http\Middleware\Authorize;
use Phattarachai\MailLogLaravel\Listeners\LogOutgoingMail;
use Phattarachai\MailLogLaravel\MailLog;
use Phattarachai\MailLogLaravel\Support\Fingerprinter;

it('merges the package config', function () {
    expect(config('mail-log.tables.groups'))->toBe('mail_log_groups')
        ->and(config('mail-log.tables.events'))->toBe('mail_logs')
        ->and(config('mail-log.fingerprint.default_mode'))->toBe(['class', 'model'])
        ->and(config('mail-log.ui.middleware'))->toContain('web')
        ->and(config('mail-log.ui.middleware'))->toContain(Authorize::class);
});

it('registers the Fingerprinter as a singleton', function () {
    expect(app(Fingerprinter::class))
        ->toBeInstanceOf(Fingerprinter::class)
        ->and(app(Fingerprinter::class))->toBe(app(Fingerprinter::class));
});

it('wires the outgoing-mail event listeners when enabled', function () {
    expect(Event::hasListeners(MessageSending::class))->toBeTrue()
        ->and(Event::hasListeners(MessageSent::class))->toBeTrue()
        ->and(Event::hasListeners(JobFailed::class))->toBeTrue();
});

it('returns 403 from the dashboard by default (debug-only gate)', function () {
    $response = $this->get('/mail-log');

    $response->assertForbidden();
});

it('allows the dashboard once MailLog::auth approves the request', function () {
    MailLog::auth(fn () => true);

    $this->get('/mail-log')->assertOk();
});

it('honors MailLog::auth returning false', function () {
    MailLog::auth(fn () => false);

    $this->get('/mail-log')->assertForbidden();
});

it('falls back to APP_DEBUG when no auth callback is registered', function () {
    config()->set('app.debug', true);

    $this->get('/mail-log')->assertOk();
});

it('exposes a mail-log:install Artisan command', function () {
    $this->artisan('mail-log:install')->assertSuccessful();
});

it('registers the package publish tags', function () {
    $tags = \Illuminate\Support\ServiceProvider::publishableGroups();

    expect($tags)->toContain('mail-log-config')
        ->and($tags)->toContain('mail-log-migrations')
        ->and($tags)->toContain('mail-log-views');
});

it('exposes the LogOutgoingMail listener handlers', function () {
    $listener = new LogOutgoingMail();

    expect(method_exists($listener, 'handleSending'))->toBeTrue()
        ->and(method_exists($listener, 'handleSent'))->toBeTrue()
        ->and(method_exists($listener, 'handleFailed'))->toBeTrue();
});
