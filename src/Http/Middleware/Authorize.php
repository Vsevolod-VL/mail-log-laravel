<?php

declare(strict_types=1);

namespace VsevolodVL\MailLogLaravel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use VsevolodVL\MailLogLaravel\MailLog;
use Symfony\Component\HttpFoundation\Response;

class Authorize
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(MailLog::check($request), 403);

        return $next($request);
    }
}
