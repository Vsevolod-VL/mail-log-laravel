@props(['group'])

@php
    /** @var \VsevolodVL\MailLogLaravel\Models\MailLogGroup $group */
@endphp

<div
        x-data="bodyPreview(
        @js((string) $group->html_body),
        @js((string) $group->text_body)
    )"
        class="space-y-3"
>
    <div class="flex flex-wrap items-center gap-2 text-sm">
        <div class="inline-flex rounded-md border border-zinc-200 bg-white p-0.5">
            <button
                    type="button"
                    @click="setMode('html')"
                    :class="mode === 'html' ? 'bg-zinc-100 text-zinc-900' : 'text-zinc-500 hover:text-zinc-700'"
                    class="rounded px-2.5 py-1 text-xs font-medium"
            >HTML
            </button>
            <button
                    type="button"
                    @click="setMode('text')"
                    :class="mode === 'text' ? 'bg-zinc-100 text-zinc-900' : 'text-zinc-500 hover:text-zinc-700'"
                    class="rounded px-2.5 py-1 text-xs"
            >Text
            </button>
            <button
                    type="button"
                    @click="setMode('source')"
                    :class="mode === 'source' ? 'bg-zinc-100 text-zinc-900' : 'text-zinc-500 hover:text-zinc-700'"
                    class="rounded px-2.5 py-1 text-xs"
            >Quelltext
            </button>
        </div>
        <span class="ml-2 inline-flex items-center gap-1.5 text-xs text-zinc-500">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24"
                 stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round"
                                                                d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
            dargestellt in isoliertem <code class="font-mono text-[11px]">&lt;iframe srcdoc&gt;</code>
        </span>
    </div>

    <p class="text-[11px] text-zinc-400">
        Der Inhalt zeigt die <span class="font-medium text-zinc-600">erste Sendung</span> in dieser Gruppe.
        Empfängerspezifische Inhaltsvariationen (signierte URLs, Magic Links, personalisierter Text) werden nicht
        erfasst.
    </p>

    <div class="rounded-lg border border-zinc-200 bg-zinc-100 p-3">
        <iframe
                x-ref="frame"
                sandbox="allow-same-origin"
                class="mail-log-body-preview rounded-md"
                referrerpolicy="no-referrer"
        ></iframe>
    </div>
</div>
