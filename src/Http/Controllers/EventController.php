<?php

declare(strict_types=1);

namespace Phattarachai\MailLogLaravel\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EventController
{
    public function show(Request $request, int|string $group, int|string $event): Response
    {
        return response('mail-log:event', 200);
    }
}
