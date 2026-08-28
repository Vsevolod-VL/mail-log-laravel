<?php

namespace Workbench\App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use VsevolodVL\MailLogLaravel\MailLogServiceProvider;

class WorkbenchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(MailLogServiceProvider::class);
    }

    public function boot(): void
    {
        // Diagnostic route
        Route::get('/_debug', function () {
            $finder = view()->getFinder();
            return response()->json([
                'hints'        => $finder->getHints(),
                'mail_log_config' => config('mail-log'),
                'providers'    => array_keys(app()->getLoadedProviders()),
            ]);
        });
    }
}
