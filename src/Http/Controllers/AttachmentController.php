<?php

declare(strict_types=1);

namespace Phattarachai\MailLogLaravel\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AttachmentController
{
    public function show(Request $request, int|string $group, int|string $media): Response
    {
        return response('', 404);
    }
}
