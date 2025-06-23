<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CartResource\Pages;

use App\Enums\CartStatusEnum;
use App\Filament\Admin\Resources\CartResource;
use App\Models\Ticketing\Cart;
use App\Services\CheckoutService;
use App\Services\StripeService;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewCart extends ViewRecord
{
    protected static string $resource = CartResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('complete')
                ->label('Complete Cart')
                ->visible(function (Cart $record): bool {
                    return $record->status === CartStatusEnum::Expired &&
                        $record->stripe_checkout_id &&
                        Auth::authenticate()->can('carts.complete');
                })
                ->requiresConfirmation()
                ->modalHeading('Complete Cart')
                ->modalDescription('Completing this cart will finalize the items in it and create an order, WITHOUT requiring payment.')
                ->action(function (Cart $record, StripeService $stripeService, CheckoutService $checkoutService): void {
                    $session = $stripeService->getCheckoutSession($record->stripe_checkout_id);
                    $checkoutService->resolveCompletedCheckoutSession($session);
                }),
        ];
    }
}
