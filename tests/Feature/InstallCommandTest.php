<?php

declare(strict_types=1);
use Illuminate\Support\Facades\Schema;

beforeEach(function (): void {
    $this->envPath = base_path('.env');
    $this->originalEnv = is_file($this->envPath) ? (string) file_get_contents($this->envPath) : null;

    file_put_contents($this->envPath, "APP_NAME=Testing\n");

    foreach ((array) glob(database_path('migrations/*.php')) as $publishedMigration) {
        if (is_string($publishedMigration) && (
            str_contains(basename($publishedMigration), 'create_mail_log_groups_table')
            || str_contains(basename($publishedMigration), 'create_mail_logs_table')
            || str_contains(basename($publishedMigration), 'create_media_table')
        )) {
            @unlink($publishedMigration);
        }
    }

    @unlink(config_path('mail-log.php'));
});

afterEach(function (): void {
    if ($this->originalEnv !== null) {
        file_put_contents($this->envPath, $this->originalEnv);
    } else {
        @unlink($this->envPath);
    }

    foreach ((array) glob(database_path('migrations/*.php')) as $publishedMigration) {
        if (is_string($publishedMigration) && (
            str_contains(basename($publishedMigration), 'create_mail_log_groups_table')
            || str_contains(basename($publishedMigration), 'create_mail_logs_table')
            || str_contains(basename($publishedMigration), 'create_media_table')
        )) {
            @unlink($publishedMigration);
        }
    }

    @unlink(config_path('mail-log.php'));
});

it('publishes config + migrations + env keys + snippets on a fresh install', function (): void {
    $this->artisan('mail-log:install')
        ->expectsConfirmation('Run `php artisan migrate` now?', 'no')
        ->assertExitCode(0);

    expect(is_file(config_path('mail-log.php')))->toBeTrue();

    $env = (string) file_get_contents($this->envPath);
    expect($env)->toContain('MAIL_LOG_ENABLED=true')
        ->and($env)->toContain('MAIL_LOG_RETENTION_DAYS=365')
        ->and($env)->toContain('MAIL_LOG_UI_PATH=mail-log');

    $files = (array) glob(database_path('migrations/*.php'));
    $names = array_map(fn ($f) => basename((string) $f), $files);

    expect($names)->toContain(...array_filter($names, fn ($n) => str_contains((string) $n, 'create_mail_log_groups_table')));
    expect($names)->toContain(...array_filter($names, fn ($n) => str_contains((string) $n, 'create_mail_logs_table')));
});

it('prints the AppServiceProvider auth gate + HasMailLog trait snippets', function (): void {
    $this->artisan('mail-log:install')
        ->expectsConfirmation('Run `php artisan migrate` now?', 'no')
        ->expectsOutputToContain('MailLogGroup::registerMorphMap();')
        ->expectsOutputToContain('MailLog::auth(function ($request)')
        ->expectsOutputToContain('use VsevolodVL\\MailLogLaravel\\Concerns\\HasMailLog;')
        ->expectsOutputToContain('protected function mailLogModel()')
        ->expectsOutputToContain('return $this->withMailLog(new Headers())')
        ->expectsOutputToContain('model:prune')
        ->assertExitCode(0);
});

it('does not write files when --dry-run is passed', function (): void {
    $this->artisan('mail-log:install', ['--dry-run' => true])
        ->expectsOutputToContain('--dry-run: no files were modified.')
        ->expectsOutputToContain('Would publish: config/mail-log.php')
        ->expectsOutputToContain('Would set in .env')
        ->assertExitCode(0);

    expect(is_file(config_path('mail-log.php')))->toBeFalse();

    $env = (string) file_get_contents($this->envPath);
    expect($env)->not->toContain('MAIL_LOG_ENABLED');

    $files = (array) glob(database_path('migrations/*_create_mail_log_groups_table.php'));
    expect($files)->toBe([]);
});

it('preserves a pre-existing MAIL_LOG_RETENTION_DAYS value', function (): void {
    file_put_contents($this->envPath, "APP_NAME=Testing\nMAIL_LOG_RETENTION_DAYS=30\n");

    $this->artisan('mail-log:install')
        ->expectsConfirmation('Run `php artisan migrate` now?', 'no')
        ->assertExitCode(0);

    $env = (string) file_get_contents($this->envPath);

    expect($env)->toContain('MAIL_LOG_RETENTION_DAYS=30')
        ->and(substr_count($env, 'MAIL_LOG_RETENTION_DAYS'))->toBe(1);
});

it('skips the mail-log migration publish when one already exists', function (): void {
    $stub = database_path('migrations/2026_05_01_000000_create_mail_log_groups_table.php');
    file_put_contents($stub, "<?php\n// stub from a previous install\n");

    $this->artisan('mail-log:install')
        ->expectsConfirmation('Run `php artisan migrate` now?', 'no')
        ->expectsOutputToContain('Mail Log migrations already published — skipping.')
        ->assertExitCode(0);

    expect(file_get_contents($stub))->toContain('stub from a previous install');
});

it('publishes the Spatie media migration when the media table is missing', function (): void {
    Schema::dropIfExists('media');

    $this->artisan('mail-log:install')
        ->expectsConfirmation('Run `php artisan migrate` now?', 'no')
        ->assertExitCode(0);

    $files = (array) glob(database_path('migrations/*_create_media_table.php'));

    expect($files)->not->toBe([]);
});

it('skips the Spatie media migration when the media table already exists', function (): void {
    $this->artisan('mail-log:install')
        ->expectsConfirmation('Run `php artisan migrate` now?', 'no')
        ->expectsOutputToContain('Spatie media table already present — no migration publish needed.')
        ->assertExitCode(0);
});
