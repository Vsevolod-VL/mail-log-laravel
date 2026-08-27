@props(['event'])

@php
    use VsevolodVL\MailLogLaravel\MailLog;

    /** @var VsevolodVL\MailLogLaravel\Models\MailLog $event */
    $primaryRecipient = collect($event->to ?? [])->first();
    $extraRecipients = max(0, count($event->to ?? []) - 1)
        + count($event->cc ?? [])
        + count($event->bcc ?? []);
    $isFailed = $event->status->value === 'failed';
@endphp

<tr class="hover:bg-zinc-50">
    <td class="px-3 py-2 align-top">
        <x-mail-log::status-badge :status="$event->status"/>
    </td>
    <td class="px-3 py-2 align-top text-zinc-700">
        @if ($primaryRecipient)
            <div class="font-mono text-[12px]">{{ $primaryRecipient }}</div>
            @if ($extraRecipients > 0)
                <div class="font-mono text-[11px] text-zinc-400">+{{ $extraRecipients }} weitere</div>
            @endif
        @else
            <span class="text-zinc-400">—</span>
        @endif
    </td>
    <td class="px-3 py-2 align-top font-mono text-[11px] text-zinc-500">{{ MailLog::dt($event->created_at) }}</td>
    <td class="px-3 py-2 align-top font-mono text-[11px] text-zinc-500">
        @if ($event->seconds !== null)
            {{ number_format($event->seconds, 3) }}s
        @else
            —
        @endif
    </td>
    <td class="px-3 py-2 align-top">
        @if ($isFailed && $event->error_message)
            <button
                    type="button"
                    @click="toggle({{ $event->id }})"
                    class="inline-flex items-center gap-1 text-[11px] font-medium text-rose-700 hover:underline"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2"
                     stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
                </svg>
                <span x-text="isOpen({{ $event->id }}) ? 'Fehler ausblenden' : 'Fehler anzeigen'"></span>
            </button>
        @endif
    </td>
</tr>
@if ($isFailed && $event->error_message)
    <tr x-show="isOpen({{ $event->id }})" x-cloak>
        <td colspan="5" class="px-3 pb-3">
            <div class="rounded-md border border-rose-100 bg-rose-50 px-3 py-2 font-mono text-[11px] text-rose-700">
                {{ $event->error_message }}
            </div>
        </td>
    </tr>
@endif
