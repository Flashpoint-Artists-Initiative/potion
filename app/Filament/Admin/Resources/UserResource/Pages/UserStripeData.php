<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\UserResource\Pages;

use App\Enums\CartStatusEnum;
use App\Filament\Admin\Resources\CartResource;
use App\Filament\Admin\Resources\EventResource;
use App\Filament\Admin\Resources\OrderResource;
use App\Filament\Admin\Resources\UserResource;
use App\Models\User;
use App\Services\StripeService;
use Filament\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Infolists\Components;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Auth;
use NumberFormatter;

class UserStripeData extends Page implements HasForms, HasInfolists
{
    use InteractsWithForms, InteractsWithInfolists, InteractsWithRecord;

    protected static string $resource = UserResource::class;

    protected static string $view = 'filament.admin.resources.user-resource.pages.user-stripe-data';

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'Stripe Data';

    /** @var array<string, mixed> */
    public ?array $paymentIntents = null;

    protected StripeService $stripeService;

    public function boot(StripeService $stripeService): void
    {
        $this->stripeService = $stripeService;
    }

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        abort_unless(Auth::authenticate()->can('users.viewStripeData'), 403);
    }

    protected function getHeaderActions(): array
    {
        return [
            // Action::make('fetchCheckoutSessions')
            //     ->label('Fetch Checkout Sessions')
            //     ->action(fn () => $this->fetchCheckoutSessions()),
            Action::make('fetchPaymentIntents')
                ->label('Fetch Payment Intents')
                ->action(fn () => $this->fetchPaymentIntents()),
        ];
    }

    // protected function fetchCheckoutSessions(StripeService $stripeService): void
    // {
    //     $stripeService->fetchCheckoutSessions($this->record);
    //     $this->notify('success', 'Checkout sessions fetched successfully.');
    // }

    public function paymentIntentsInfolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->state([
                'paymentIntents' => $this->paymentIntents,
            ])
            ->schema([
                Components\RepeatableEntry::make('paymentIntents')
                    ->label('Payment Intents')
                    ->statePath('paymentIntents')
                    ->schema([
                        Components\TextEntry::make('id')
                            ->label('ID')
                            ->color('primary')
                            ->weight('bold')
                            ->columnSpanFull()
                            ->url(fn (string $state): string => config('services.stripe.dashboard_url') . "/payments/{$state}", true),
                        Components\TextEntry::make('created_at')
                            ->label('Date')
                            ->dateTime('F jS, Y g:i A T', 'America/New_York'),
                        Components\TextEntry::make('amount')
                            ->label('Amount'),
                        Components\TextEntry::make('metadata.ticket_quantity')
                            ->label('Quantity'),
                        Components\Grid::make(2)
                            ->schema([
                                Components\TextEntry::make('status.stripe')
                                    ->icon('heroicon-o-credit-card')
                                    ->iconColor(fn (string $state): string => match ($state) {
                                        'succeeded' => 'success',
                                        default => 'danger',
                                    })
                                    ->formatStateUsing(fn (string $state): string => str($state)->replace('_', ' ')->title()->toString())
                                    ->label('Stripe Status'),
                                Components\TextEntry::make('status.cart')
                                    ->icon('heroicon-o-shopping-cart')
                                    ->iconColor(fn (CartStatusEnum|false $state): string => match ($state) {
                                        CartStatusEnum::Completed => 'success',
                                        default => 'danger',
                                    })
                                    ->formatStateUsing(fn (CartStatusEnum|false $state): string => $state ? $state->getLabel() : 'Missing Cart')
                                    ->label('Cart Status'),
                            ])
                            ->columnSpan(2),
                        Components\Grid::make(3)
                            ->schema([
                                Components\IconEntry::make('metadata.event_id')
                                    ->label('')
                                    ->icon('heroicon-o-calendar-days')
                                    ->color('primary')
                                    ->url(fn (?string $state): ?string => ! $state ? null : EventResource::getUrl('view', ['record' => $state]), true),
                                Components\IconEntry::make('metadata.cart_id')
                                    ->label('')
                                    ->icon('heroicon-o-shopping-cart')
                                    ->color('primary')
                                    ->url(fn (?string $state): ?string => ! $state ? null : CartResource::getUrl('view', ['record' => $state]), true),
                                Components\IconEntry::make('metadata.order_id')
                                    ->label('')
                                    ->icon('heroicon-o-shopping-bag')
                                    ->color('primary')
                                    ->url(fn (?string $state): ?string => ! $state ? null : OrderResource::getUrl('view', ['record' => $state]), true),
                            ])
                            ->columnSpan(1),
                    ])
                    ->columns(3)
                    ->grid(2),
            ]);
    }

    protected function fetchPaymentIntents(): void
    {
        /** @var User $user */
        $user = $this->record;
        $paymentIntents = $this->stripeService->getUserPaymentIntents($user);

        $this->paymentIntents = [];
        $formatter = new NumberFormatter('en_US', NumberFormatter::CURRENCY);

        foreach ($paymentIntents as $paymentIntent) {
            $cart = $this->stripeService->getCartFromPaymentIntent($paymentIntent);
            $disputes = $this->stripeService->getDisputesFromPaymentIntent($paymentIntent);
            $refunds = $this->stripeService->getRefundsFromPaymentIntent($paymentIntent);

            $status = $paymentIntent->status;
            
            if (! empty($disputes)) {
                $status = 'dispute ' . $disputes[0]->status;
            } 
            
            if (! empty($refunds)) {
                $status = 'refund ' . $refunds[0]->status;
            }

            $this->paymentIntents[$paymentIntent->id] = [
                'id' => $paymentIntent->id,
                'amount' => $formatter->formatCurrency($paymentIntent->amount / 100, 'USD'),
                'status' => [
                    'stripe' => $status,
                    'cart' => $cart ? $cart->status : false,
                ],
                'created_at' => $paymentIntent->created,
                'metadata' => $paymentIntent->metadata->toArray(),
            ];
        }
    }
}
