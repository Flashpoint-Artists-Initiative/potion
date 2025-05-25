<x-filament-panels::page>
    <div>
        @if ($showWaiverWarning)
        <x-notification-banner color="danger">
            You must {{ $this->signWaiverAction }} before you can use your tickets.
        </x-notification-banner>
        @endif
        @if ($hasMultipleTickets)
            <x-notification-banner color="info">
                You have multiple tickets for this event! Every person attending the event must have their own POTION account and ticket. {{ $this->ticketInfoAction }}
            </x-notification-banner>
        @endif
        @if ($showCellServiceWarning)
            <x-notification-banner color="warning">
                In case there is poor cell service at the event, we strongly recommend you take a screenshot of or print your QR Code.
            </x-notification-banner>
        @endif
    </div>
    {{  $this->ticketsInfolist }}
</x-filament-panels::page>
