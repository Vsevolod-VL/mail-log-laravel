<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Storage;
use Phattarachai\MailLogLaravel\Enums\MailLogStatus;
use Phattarachai\MailLogLaravel\MailLog;
use Phattarachai\MailLogLaravel\Models\MailLog as MailLogEvent;
use Phattarachai\MailLogLaravel\Models\MailLogGroup;

beforeEach(function () {
    Relation::morphMap([], false);
    MailLogGroup::registerMorphMap();

    config()->set('mail.default', 'array');
    config()->set('mail.mailers.array', ['transport' => 'array']);
    config()->set('mail.from', ['address' => 'app@example.com', 'name' => 'App']);

    MailLog::auth(fn () => true);
});

it('lists groups on the index with sent-count and recipient summary', function () {
    $group = MailLogGroup::factory()->create([
        'subject' => 'Order #1042 shipped',
        'sent_count' => 3,
        'failed_count' => 0,
        'latest_status' => MailLogStatus::Sent,
        'mailable_class' => 'App\\Mail\\OrderShipped',
        'mailer' => 'smtp',
    ]);

    MailLogEvent::factory()->for($group, 'group')->create(['to' => ['a@example.com']]);
    MailLogEvent::factory()->for($group, 'group')->create(['to' => ['b@example.com']]);
    MailLogEvent::factory()->for($group, 'group')->create(['to' => ['c@example.com']]);

    $response = $this->get(route('mail-log.index'));

    $response->assertOk()
        ->assertSee('Order #1042 shipped')
        ->assertSee('OrderShipped')
        ->assertSee('smtp')
        ->assertSee('+2 more');
});

it('filters the index by status', function () {
    MailLogGroup::factory()->create([
        'subject' => 'Sent A',
        'latest_status' => MailLogStatus::Sent,
        'sent_count' => 1,
    ]);
    MailLogGroup::factory()->create([
        'subject' => 'Failed B',
        'latest_status' => MailLogStatus::Failed,
        'failed_count' => 1,
    ]);

    $response = $this->get(route('mail-log.index', ['status' => 'failed']));

    $response->assertOk()
        ->assertSee('Failed B')
        ->assertDontSee('Sent A');
});

it('filters the index by has_failures', function () {
    MailLogGroup::factory()->create([
        'subject' => 'Clean run',
        'sent_count' => 5,
        'failed_count' => 0,
    ]);
    MailLogGroup::factory()->create([
        'subject' => 'Mixed run',
        'sent_count' => 4,
        'failed_count' => 1,
        'latest_status' => MailLogStatus::Sent,
    ]);

    $response = $this->get(route('mail-log.index', ['has_failures' => 1]));

    $response->assertOk()
        ->assertSee('Mixed run')
        ->assertDontSee('Clean run');
});

it('filters the index by search across subject, mailable class, and recipients', function () {
    $bySubject = MailLogGroup::factory()->create(['subject' => 'Daily digest', 'mailable_class' => 'App\\Mail\\Digest']);
    $byClass = MailLogGroup::factory()->create(['subject' => 'Welcome aboard', 'mailable_class' => 'App\\Mail\\OrderShipped']);
    $byRecipient = MailLogGroup::factory()->create(['subject' => 'Password reset', 'mailable_class' => 'App\\Mail\\PasswordReset']);
    MailLogEvent::factory()->for($byRecipient, 'group')->create(['to' => ['order-specific@example.com']]);

    expect($this->get(route('mail-log.index', ['search' => 'Digest']))->getContent())
        ->toContain('Daily digest')
        ->not->toContain('Welcome aboard');

    expect($this->get(route('mail-log.index', ['search' => 'OrderShipped']))->getContent())
        ->toContain('Welcome aboard')
        ->not->toContain('Daily digest');

    expect($this->get(route('mail-log.index', ['search' => 'order-specific']))->getContent())
        ->toContain('Password reset')
        ->not->toContain('Daily digest');
});

it('shows the body preview iframe and sends table on the group detail page', function () {
    $group = MailLogGroup::factory()->create([
        'subject' => 'Order #1042 shipped',
        'sent_count' => 2,
        'html_body' => '<p>Order shipped</p>',
        'mailable_class' => 'App\\Mail\\OrderShipped',
        'mailer' => 'smtp',
    ]);
    MailLogEvent::factory()->for($group, 'group')->create(['to' => ['a@example.com']]);
    MailLogEvent::factory()->for($group, 'group')->create(['to' => ['b@example.com']]);

    $response = $this->get(route('mail-log.show', $group));

    $response->assertOk()
        ->assertSee('Order #1042 shipped')
        ->assertSee('Sends (2)')
        ->assertSee('a@example.com')
        ->assertSee('b@example.com')
        ->assertSee('App\\Mail\\OrderShipped', escape: false)
        ->assertSeeText('bodyPreview');
});

it('returns event JSON via /events/{event}', function () {
    $group = MailLogGroup::factory()->create();
    $event = MailLogEvent::factory()->for($group, 'group')->create([
        'to' => ['target@example.com'],
        'status' => MailLogStatus::Sent,
        'seconds' => 0.123,
    ]);

    $response = $this->get(route('mail-log.event', ['group' => $group, 'event' => $event]));

    $response->assertOk()
        ->assertJsonPath('id', $event->id)
        ->assertJsonPath('group_id', $group->id)
        ->assertJsonPath('status', 'sent')
        ->assertJsonPath('to.0', 'target@example.com')
        ->assertJsonPath('seconds', 0.123);
});

it('404s when the event does not belong to the group', function () {
    $group = MailLogGroup::factory()->create();
    $other = MailLogGroup::factory()->create();
    $event = MailLogEvent::factory()->for($other, 'group')->create();

    $this->get(route('mail-log.event', ['group' => $group, 'event' => $event]))->assertNotFound();
});

it('downloads an attachment via /attachments/{media}', function () {
    config()->set('mail-log.attachments.disk', 'public');
    Storage::fake('public');

    $group = MailLogGroup::factory()->create();
    $tempPath = tempnam(sys_get_temp_dir(), 'mail_log_test_');
    file_put_contents($tempPath, 'invoice contents');

    $media = $group->addMedia($tempPath)
        ->usingFileName('invoice.pdf')
        ->toMediaCollection('attachments');

    $response = $this->get(route('mail-log.attachment', ['group' => $group, 'media' => $media->id]));

    $response->assertOk();
    expect($response->headers->get('content-disposition'))->toContain('invoice.pdf');
});

it('test-send dispatches a mail and creates a new TestMail group', function () {
    $response = $this
        ->from(route('mail-log.index'))
        ->post(route('mail-log.test-send'), [
            'email' => 'tester@example.com',
            'message' => 'Hello from the test',
        ]);

    $response->assertRedirect(route('mail-log.index'));

    $group = MailLogGroup::query()
        ->where('mailable_class', \Phattarachai\MailLogLaravel\Mail\TestMail::class)
        ->firstOrFail();

    expect($group->sent_count)->toBe(1);
    expect(MailLogEvent::query()->where('group_id', $group->id)->count())->toBe(1);
});

it('test-send route 404s when test-send is disabled', function () {
    config()->set('mail-log.test_send.enabled', false);

    $this->post(route('mail-log.test-send'), ['email' => 'x@example.com'])->assertNotFound();
});

it('deleting a group cascade-removes its events', function () {
    $group = MailLogGroup::factory()->create();
    MailLogEvent::factory()->for($group, 'group')->count(3)->create();

    $this
        ->from(route('mail-log.show', $group))
        ->delete(route('mail-log.destroy', $group))
        ->assertRedirect(route('mail-log.index'));

    expect(MailLogGroup::query()->find($group->id))->toBeNull()
        ->and(MailLogEvent::query()->where('group_id', $group->id)->count())->toBe(0);
});

it('blocks all UI routes when MailLog::auth returns false', function () {
    MailLog::auth(fn () => false);

    $group = MailLogGroup::factory()->create();

    $this->get(route('mail-log.index'))->assertForbidden();
    $this->get(route('mail-log.show', $group))->assertForbidden();
    $this->delete(route('mail-log.destroy', $group))->assertForbidden();
    $this->post(route('mail-log.test-send'), ['email' => 'x@example.com'])->assertForbidden();
});

it('renders the header back-link when back_link config has a url but no label key', function () {
    // Installs published before 0.2.0 carry a back_link array with only `url`
    // (the `label` key was added later). The header must not blow up with an
    // "Undefined array key label" fatal — it should fall back to "Back".
    config()->set('mail-log.ui.back_link', ['url' => 'https://app.example.com']);

    MailLogGroup::factory()->create();

    $this->get(route('mail-log.index'))
        ->assertOk()
        ->assertSee('https://app.example.com')
        ->assertSee('Back');
});

it('inlines the dist CSS + JS bundles via the asset helpers', function () {
    $cssHtml = (string) MailLog::css();
    $jsHtml = (string) MailLog::js();

    expect($cssHtml)->toStartWith('<style>')
        ->and($cssHtml)->toEndWith('</style>')
        ->and(strlen($cssHtml))->toBeGreaterThan(200);

    expect($jsHtml)->toContain('<script type="module">')
        ->and($jsHtml)->toContain('window.MailLog')
        ->and(strlen($jsHtml))->toBeGreaterThan(500);
});
