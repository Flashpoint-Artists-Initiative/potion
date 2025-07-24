<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use App\Models\Ticketing\Order;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\StripeService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Livewire\Attributes\Url;

class CheckoutComplete extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.app.pages.checkout-complete';

    #[Url]
    public ?string $sessionId = null;

    public ?string $checkoutCompleteContent;

    public function mount(StripeService $stripeService, CheckoutService $checkoutService, CartService $cartService): void
    {
        if (! $this->sessionId) {
            $this->redirect(PurchaseTickets::getUrl());

            Notification::make()
                ->title('Cart Error')
                ->body('Cannot find your cart. If you feel you\'ve reached this page in error, please contact us.')
                ->danger()
                ->send();

            return;
        }

        $session = $stripeService->getCheckoutSession($this->sessionId);

        if ($session->status !== 'complete') {
            $this->redirect(PurchaseTickets::getUrl());

            Notification::make()
                ->title('Cart Error')
                ->body('This cart is no longer valid. Please try again.')
                ->danger()
                ->send();
            
            return;
        }

        $order = Order::whereStripeCheckoutId($session->id)->first();

        if ($order) {
            $this->redirect(Dashboard::getUrl());

            Notification::make()
                ->title('Checkout Already Completed')
                ->body('Your order has already been processed. Thank you for your purchase!')
                ->warning()
                ->send();
            
            return;
        }

        $checkoutService->resolveCompletedCheckoutSession($session);

        $cart = $cartService->getCartFromSessionId($this->sessionId);

        $this->checkoutCompleteContent = $cart->event->checkoutCompleteContent->formattedContent ?? null;
    }
}
