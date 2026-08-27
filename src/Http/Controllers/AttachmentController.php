<?php

declare(strict_types=1);

namespace VsevolodVL\MailLogLaravel\Http\Controllers;

use Illuminate\Http\Request;
use VsevolodVL\MailLogLaravel\Models\MailLogGroup;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AttachmentController
{
    public function show(Request $request, MailLogGroup $group, Media $media): BinaryFileResponse
    {
        abort_unless(
            $media->model_type === $group->getMorphClass()
                && (string) $media->model_id === (string) $group->getKey(),
            404,
        );

        return response()->download(
            $media->getPath(),
            $media->file_name,
            ['Content-Type' => $media->mime_type ?: 'application/octet-stream'],
        );
    }
}
