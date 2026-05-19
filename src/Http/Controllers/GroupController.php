<?php

declare(strict_types=1);

namespace Phattarachai\MailLogLaravel\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Phase 4b implements the real list / show / destroy actions. Phase 1 ships
 * stubs so route registration succeeds and the dashboard mount-point test
 * can hit `GET /{ui.path}` end-to-end.
 */
class GroupController
{
    public function index(Request $request): Response
    {
        return response('mail-log:index', 200);
    }

    public function show(Request $request, int|string $group): Response
    {
        return response('mail-log:show', 200);
    }

    public function destroy(Request $request, int|string $group): Response
    {
        return response('', 204);
    }
}
