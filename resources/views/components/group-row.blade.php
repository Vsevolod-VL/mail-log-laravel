@props(['group'])

@php
    use VsevolodVL\MailLogLaravel\MailLog;
    use VsevolodVL\MailLogLaravel\Models\MailLogGroup;

    /** @var MailLogGroup $group */
    $recipients = $group->uniqueRecipients();
    $firstRecipient = $recipients->first();
    $extra = max(0, $recipients->count() - 1);
    $multi = ($group->sent_count + $group->failed_count) > 1;
    $isFailed = $group->latest_status?->value === 'failed';
    $latestError = $isFailed
        ? optional($group->latestEvent)->error_message
        : null;
@endphp

<tr class="hover:bg-zinc-50">
    <td class="whitespace-nowrap py-2.5 align-top">
        <a href="{{ route('mail-log.show', $group) }}" class="block font-mono">
            <div class="text-xs text-zinc-700">{{ MailLog::dt($group->updated_at) }}</div>
            @if ($multi)
                <div class="text-[10px] text-zinc-400">zuerst {{ optional($group->created_at)->format('H:i') }}</div>
            @endif
        </a>
    </td>
    <td class="px-3 py-2.5 align-top">
        @if ($group->latest_status)
            <x-mail-log::status-badge :status="$group->latest_status"/>
        @endif
        @if ($group->failed_count > 0 && !$isFailed)
            <div class="mt-1">
                <x-mail-log::failed-error-chip :count="$group->failed_count"/>
            </div>
        @endif
    </td>
    <td class="px-3 py-2.5 align-top">
        <a href="{{ route('mail-log.show', $group) }}" class="font-medium text-zinc-900 hover:underline">
            {{ $group->subject ?: '(kein Betreff)' }}
        </a>
        @if ($latestError)
            <x-mail-log::failed-error-chip :message="$latestError"/>
        @endif
    </td>
    <td class="px-3 py-2.5 align-top text-zinc-600">
        @if ($firstRecipient)
            <span>{{ $firstRecipient }}</span>
            @if ($extra > 0)
                <span
                        class="ml-1 inline-flex h-5 items-center rounded-full bg-zinc-100 px-1.5 font-mono text-[11px] text-zinc-600">+{{ $extra }} weitere</span>
            @endif
        @else
            <span class="text-zinc-400">—</span>
        @endif
    </td>
    <td class="px-3 py-2.5 align-top">
        @if ($group->mailable_class)
            <span class="font-mono text-[11px] text-zinc-500">{{ class_basename($group->mailable_class) }}</span>
        @elseif ($group->notification_class)
            <span class="font-mono text-[11px] text-zinc-500">{{ class_basename($group->notification_class) }}</span>
        @else
            <span class="text-zinc-400">—</span>
        @endif
    </td>
    <td class="px-3 py-2.5 align-top">
        @if ($group->mailer)
            <span
                    class="inline-flex items-center rounded-md bg-zinc-100 px-1.5 py-0.5 font-mono text-[11px] text-zinc-600">{{ $group->mailer }}</span>
        @else
            <span class="text-zinc-400">—</span>
        @endif
    </td>
    <td class="px-3 py-2.5 text-right align-top font-mono text-[11px] text-zinc-500">{{ $group->sent_count + $group->failed_count }}</td>
</tr>
