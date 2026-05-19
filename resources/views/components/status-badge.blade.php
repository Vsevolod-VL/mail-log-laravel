@props(['status'])

@php
    /** @var \Phattarachai\MailLogLaravel\Enums\MailLogStatus $status */
    use Phattarachai\MailLogLaravel\Enums\MailLogStatus;

    $classes = match ($status) {
        MailLogStatus::Sent => 'bg-emerald-50 text-emerald-700',
        MailLogStatus::Failed => 'bg-rose-50 text-rose-700',
        MailLogStatus::Pending => 'bg-amber-50 text-amber-700',
    };
    $dot = match ($status) {
        MailLogStatus::Sent => 'bg-emerald-500',
        MailLogStatus::Failed => 'bg-rose-500',
        MailLogStatus::Pending => 'bg-amber-500',
    };
    $label = strtolower($status->getLabel());
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium $classes"]) }}>
    <span class="h-1.5 w-1.5 rounded-full {{ $dot }}"></span>{{ $label }}
</span>
