@php use VsevolodVL\MailLogLaravel\Models\MailLogGroup; @endphp
@props(['group'])

@php
    /** @var MailLogGroup $group */
    $recipients = $group->uniqueRecipients();
@endphp

<div {{ $attributes->merge(['class' => 'rounded-lg border border-zinc-200 bg-white']) }}>
    <div class="border-b border-zinc-100 px-4 py-2.5">
        <div class="text-[11px] uppercase text-zinc-500">Eindeutige Empfänger</div>
        <div
                class="mt-0.5 font-mono text-base font-semibold tracking-tight text-zinc-900">{{ $recipients->count() }}</div>
    </div>

    @if ($recipients->isEmpty())
        <p class="px-4 py-4 text-xs text-zinc-400">Noch keine Empfänger erfasst.</p>
    @else
        <ul class="max-h-64 divide-y divide-zinc-100 overflow-y-auto text-sm">
            @foreach($group->model as $model)
                <span><strong>TO: <span class="truncate text-zinc-700">{{ json_encode($model->to) }}</span></strong></span>

                <li class="flex flex-col text-left items-left justify-between gap-2 px-4 py-1.5">
                        <div>cc: <span class="truncate text-zinc-700">{{ json_encode($model->cc) }}</span></div>
                        <div>bcc: <span class="truncate text-zinc-700">{{ json_encode($model->bcc) }}</span></div>
                </li>
            @endforeach
        </ul>
    @endif
</div>
