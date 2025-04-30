<x-filament-panels::page>
    <div>
    @if ($hasMultipleTickets)
        <x-notification-banner color="info">
            You have multiple tickets for this event! Every person attending the event must have their own POTION account and ticket. {{ $this->ticketInfoAction }}
        </x-notification-banner>
    @endif
    {{-- @if ($ticketLockdown)
        <x-notification-banner color="danger">
            This event is in read-only mode in order to move the data offline for the event. You cannot purchase or transfer tickets.
        </x-notification-banner>
    @endif --}}
    </div>
    {{  $this->ticketsInfolist }}
</x-filament-panels::page>
