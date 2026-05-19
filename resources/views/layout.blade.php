<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>{{ $title ?? config('mail-log.ui.brand', 'Mail Log') }} · {{ config('mail-log.ui.brand', 'Mail Log') }}</title>
    <link rel="stylesheet" href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600|ibm-plex-sans-thai:400,500|jetbrains-mono:400,500" />
    {{ \Phattarachai\MailLogLaravel\MailLog::css() }}
    {{ \Phattarachai\MailLogLaravel\MailLog::js() }}
</head>
<body class="min-h-screen bg-white text-zinc-900 antialiased" x-data="mailLogShell()">

@if (session('mail-log:flash'))
    <div class="border-b border-emerald-200 bg-emerald-50 px-6 py-2 text-xs text-emerald-700">
        {{ session('mail-log:flash') }}
    </div>
@endif

<header class="border-b border-zinc-200 bg-white">
    <div class="mx-auto flex max-w-7xl items-center gap-4 px-6 py-3">
        <a href="{{ route('mail-log.index') }}" class="flex items-center gap-2">
            <div class="grid h-7 w-7 place-items-center rounded-md bg-zinc-900 text-white">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25H4.5a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/></svg>
            </div>
            <span class="font-semibold tracking-tight">{{ config('mail-log.ui.brand', 'Mail Log') }}</span>
        </a>

        <div class="ml-auto flex items-center gap-1">
            <button
                type="button"
                @click="toggleTheme()"
                class="grid h-8 w-8 place-items-center rounded-md text-zinc-500 hover:bg-zinc-100"
                title="Toggle theme"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636"/><circle cx="12" cy="12" r="4" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>

            @if (config('mail-log.test_send.enabled', true))
                <x-mail-log::test-send-modal />
            @endif
        </div>
    </div>
</header>

@yield('content')

</body>
</html>
