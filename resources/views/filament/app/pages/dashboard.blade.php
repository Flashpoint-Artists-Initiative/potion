@php
    use \App\Filament\Admin\Resources\EventResource\Pages\EditPageContent;
    use \App\Filament\App\Pages\PurchaseTickets;
    use App\Filament\App\Clusters\UserPages\Pages\TicketTransfers;
@endphp
<x-filament-panels::page>
    <div>
        {{-- Pending Reserve Tickets --}}
        @if ($hasReservedTickets)
            <x-notification-banner color="info">
                You have reserved tickets for this event! You can <x-filament::link size="large" href="{{ PurchaseTickets::getUrl() }}">purchase them</x-filament::link>
                or <x-filament::link size="large" href="{{ TicketTransfers::getUrl() }}">transfer them</x-filament::link> to someone else.
            </x-notification-banner>
        @endif
        {{-- Pending Transfers --}}
        @if ($hasPendingTransfers)
            <x-notification-banner color="info">
                You have pending ticket transfers for this event! You can accept them by going to <x-filament::link size="large" href="{{ TicketTransfers::getUrl() }}">your profile</x-filament::link>.
            </x-notification-banner>
        @endif
    </div>
    {{-- Custom content from PageContent --}}
    @if ($event?->appDashboardContent)
    <div class="prose dark:prose-invert max-w-none">
        {!! str($event?->appDashboardContent?->formattedContent)->sanitizeHtml() !!}
    </div>
    @elseif (Auth::user()->can('events.edit') && $event)
    <span>Add content to the dashboard in the <x-filament::link href="{{ EditPageContent::getUrl(['record' => $event->id], panel: 'admin') }}">Admin Panel</x-filament::link></span>
    @endif
</x-filament-panels::page>