@props(['group'])

@php
    /** @var \Phattarachai\MailLogLaravel\Models\MailLogGroup $group */
    $media = $group->getMedia((string) config('mail-log.attachments.collection', 'attachments'));
@endphp

<div {{ $attributes->merge(['class' => 'rounded-lg border border-zinc-200 bg-white']) }}>
    <div class="border-b border-zinc-100 px-4 py-2.5">
        <div class="text-[11px] uppercase tracking-wide text-zinc-500">Attachments</div>
        <div class="mt-0.5 font-mono text-base font-semibold tracking-tight text-zinc-900">{{ $media->count() }}</div>
    </div>

    @if ($media->isEmpty())
        <p class="px-4 py-4 text-xs text-zinc-400">No attachments.</p>
    @else
        <ul class="divide-y divide-zinc-100 text-sm">
            @foreach ($media as $item)
                <li class="flex items-center justify-between gap-2 px-4 py-2">
                    <div class="min-w-0">
                        <a
                            href="{{ route('mail-log.attachment', ['group' => $group, 'media' => $item->id]) }}"
                            class="block truncate text-sm font-medium text-zinc-900 hover:underline"
                            target="_blank"
                            rel="noopener"
                        >{{ $item->file_name }}</a>
                        <div class="font-mono text-[11px] text-zinc-400">
                            {{ $item->human_readable_size }} · {{ $item->mime_type ?: 'application/octet-stream' }}
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</div>
