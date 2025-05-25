<x-filament-panels::page>
    <div x-data="{
        totalCount: 0,
        max: {{ $maxTickets }},
        remaining: {{ $maxTickets }},

        init() {
            this.remaining = this.max;
            $watch('totalCount', (value) => {
                this.remaining = this.max - value;
            });
        }
    }"
    x-init="init()"
    >
        @if ($pageContent)
            <div class="prose dark:prose-invert max-w-none">
                {!! str($pageContent)->sanitizeHtml() !!}
            </div>
        @endif
        @if ($cart)
        <x-notification-banner color="info">
                You already have an existing cart with {{  $cart->quantity }} {{ Str::of('ticket')->plural($cart->quantity) }}!  It will expire {{ $cart->expiration_date->diffForHumans() }}. {{ $this->checkoutAction }}
        </x-notification-banner>
        @endif
        @if ($hasPurchasedTickets && $eventIsFuture)
        <x-notification-banner color="success">
                You've already got a ticket for this event! You can buy more, but every attendee will need to register their own account here. {{ $this->ticketInfoAction }}
        </x-notification-banner>
        @endif

        <div>
            <x-filament-panels::form wire:submit="checkout" onkeydown="return event.key != 'Enter';" class="purchase-tickets-form">
                {{ $this->form }}
            </x-filament-panels::form>
        </div>
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
