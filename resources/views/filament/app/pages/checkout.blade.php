<x-filament-panels::page>
@php
    $stripeKey = config('services.stripe.api_key');
@endphp

@assets
<script src="https://js.stripe.com/v3/"></script>
@endassets

@script
<script>
    const stripe = Stripe('{{ $stripeKey }}');
    const checkoutId = '{{ $this->checkoutId }}';

    // Don't init if there's no checkout id
    if (!checkoutId) {
        return;
    }

    initialize();

    // Create a Checkout Session
    async function initialize() {
        const fetchClientSecret = async () => {
            return '{{ $this->checkoutSecret }}';
        };

        const onComplete = () => {
            checkout.destroy();

            $wire.checkoutComplete = true;
            $wire.completeCheckout(checkoutId);
        };

        const checkout = await stripe.initEmbeddedCheckout({
            fetchClientSecret,
            onComplete,
        });

        // Mount Checkout
        checkout.mount('#stripe-checkout');
    }
</script>
@endscript

<div wire:show="!checkoutComplete" wire:cloak>
    @if ($checkoutContent)
        <div class="prose dark:prose-invert max-w-none">
            {!! str($checkoutContent)->sanitizeHtml() !!}
        </div>
    @endif
    <div class="flex">
        <x-notification-banner color="info" class="mb-2 grow">
            Your cart will expire {{ $this->cart->expiration_date->diffForHumans() }}.
        </x-notification-banner>
        {{ $this->cancelAction }}
    </div>
    <div id="stripe-checkout">
    </div>
</div>
<div wire:show="checkoutComplete" wire:cloak>
    <x-notification-banner color="success" class="mb-2 grow">
        Your purchase was successful! You can view your tickets in your profile.
    </x-notification-banner>
    @if ($checkoutCompleteContent)
        <div class="prose dark:prose-invert max-w-none">
            {!! str($checkoutCompleteContent)->sanitizeHtml() !!}
        </div>
    @endif
</div>
</x-filament-panels::page>
