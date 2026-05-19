<?php

declare(strict_types=1);

namespace Phattarachai\MailLogLaravel\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TestSendController
{
    public function store(Request $request): Response
    {
        if (! (bool) config('mail-log.test_send.enabled', true)) {
            abort(404);
        }

        return response('mail-log:test-send', 200);
    }
}
