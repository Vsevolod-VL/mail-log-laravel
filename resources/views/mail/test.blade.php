<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Mail Log test send</title>
</head>
<body style="margin:0;padding:24px;font-family:system-ui,sans-serif;background:#f4f4f5;color:#27272a">
    <div style="max-width:600px;margin:0 auto;background:#ffffff;border-radius:12px;border:1px solid #e4e4e7;padding:32px;">
        <h1 style="margin:0 0 12px;font-size:18px;font-weight:600;letter-spacing:-0.01em;">Mail Log test send</h1>
        <p style="margin:0 0 16px;font-size:14px;line-height:1.6;color:#52525b;">{{ $body }}</p>
        <p style="margin:24px 0 0;font-size:11px;color:#a1a1aa;">Sent from <code style="font-family:ui-monospace,monospace;background:#f4f4f5;padding:2px 6px;border-radius:4px;">{{ config('mail-log.ui.brand', 'Mail Log') }}</code> · {{ now()->toDateTimeString() }}</p>
    </div>
</body>
</html>
