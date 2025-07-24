<x-filament-panels::page>
    <x-notification-banner color="success" class="mb-2 grow">
        Your purchase was successful! You can view your tickets in your profile.
    </x-notification-banner>
    @if ($checkoutCompleteContent)
        <div class="prose dark:prose-invert max-w-none">
            {!! str($checkoutCompleteContent)->sanitizeHtml() !!}
        </div>
    @endif
</x-filament-panels::page>
