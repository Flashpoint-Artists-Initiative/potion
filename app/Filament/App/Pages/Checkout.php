<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use App\Models\Ticketing\Cart;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\StripeService;
use Filament\Actions\Action;
use Filament\Pages\Page;

class Checkout extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.app.pages.checkout';

    public ?string $checkoutSecret;

    public ?string $checkoutId;

    public ?Cart $cart;

    public bool $checkoutComplete = false;

    public ?string $checkoutContent;

    public ?string $checkoutCompleteContent;

    public function mount(CartService $cartService, StripeService $stripeService): void
    {
        $this->cart = $cartService->getActiveCart();

        if (! $this->cart) {
            $this->redirect(PurchaseTickets::getUrl());

            return;
        }

        $session = $stripeService->getCheckoutSession($this->cart->stripe_checkout_id);

        if ($session->status !== 'open') {
            $this->redirect(PurchaseTickets::getUrl());
        }

        $this->checkoutId = $this->cart->stripe_checkout_id;
        $this->checkoutSecret = $session->client_secret;

        $this->checkoutContent = $this->cart->event->checkoutContent->formattedContent ?? null;
        $this->checkoutCompleteContent = $this->cart->event->checkoutCompleteContent->formattedContent ?? null;
    }

    public function completeCheckout(string $sessionId, StripeService $stripeService, CheckoutService $checkoutService): void
    {
        $this->checkoutComplete = true;
        $session = $stripeService->getCheckoutSession($sessionId);
        $checkoutService->resolveCompletedCheckoutSession($session);
    }

    public function cancelAction(): Action
    {
        return Action::make('cancel')
            ->extraAttributes(['class' => 'mb-2 ms-6'])
            ->label('Cancel Order')
            ->action(function (CartService $cartService) {
                $cartService->getActiveCart()?->delete();
                $this->redirect(PurchaseTickets::getUrl());
            })
            ->color('danger')
            ->icon('heroicon-o-x-mark');
    }
}
