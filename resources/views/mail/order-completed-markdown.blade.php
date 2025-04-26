<x-mail::message>
{{-- Greeting --}}
@if (! empty($greeting))
# {{ $greeting }}
@else
@if ($level === 'error')
# @lang('Whoops!')
@else
# @lang('Hello!')
@endif
@endif

{{-- Intro Lines --}}
@foreach ($introLines as $line)
{{ $line }}

@endforeach

{{-- Action Button --}}
@isset($actionText)
<?php
    $color = match ($level) {
        'success', 'error' => $level,
        default => 'primary',
    };
?>
<x-mail::button :url="$actionUrl" :color="$color">
{{ $actionText }}
</x-mail::button>
@endisset

{{-- Outro Lines --}}
@foreach ($outroLines as $line)
{{ $line }}

@endforeach

{{-- Salutation --}}
@if (! empty($salutation))
{{ $salutation }}
@else
@lang('Regards,')<br>
{{ config('app.name') }}
@endif

# Order #{{ $order->id }}
<x-mail::panel>
<x-mail::table>
|  |  |
|----------------|------:|
@foreach ($order->ticket_data as $item)
| {{$item['quantity']}}x **{{$order->ticketTypes()->find($item['ticket_type_id'])->name}}** | ${{$order->ticketTypes()->find($item['ticket_type_id'])->price * $item['quantity']}} |
@endforeach
|  |  |
| Subtotal | ${{number_format($order->amount_subtotal / 100, 2)}} |
| Stripe Fee | ${{number_format($order->amount_fees / 100, 2)}} |
| Sales Tax | ${{number_format($order->amount_tax / 100, 2)}} |
|  |  |
| **Total** | **${{number_format($order->amount_total / 100, 2)}}** |
</x-mail::table>
</x-mail::panel>

{{-- Subcopy --}}
@isset($actionText)
<x-slot:subcopy>
@lang(
    "If you're having trouble clicking the \":actionText\" button, copy and paste the URL below\n".
    'into your web browser:',
    [
        'actionText' => $actionText,
    ]
) <span class="break-all">[{{ $displayableActionUrl }}]({{ $actionUrl }})</span>
</x-slot:subcopy>
@endisset
@isset($helpText)
<x-slot:helptext>
{{ $helpText }}
</x-slot:helptext>
@endisset
</x-mail::message>
