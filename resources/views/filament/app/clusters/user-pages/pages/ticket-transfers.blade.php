<x-filament-panels::page>
    @if ($this->showWaiverWarning)
    <x-notification-banner color="danger">
        You must {{ $this->signWaiverAction }} before you can accept a ticket transfer.
    </x-notification-banner>
    @endif
    {{ $this->table }}
    <x-filament-actions::modals />
</x-filament-panels::page>
