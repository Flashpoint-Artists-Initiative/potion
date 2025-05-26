<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\OrderResource\Pages;

use App\Filament\Admin\Resources\OrderResource;
use App\Models\Ticketing\Order;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    public function getTitle(): string|Htmlable
    {
        /** @var Order $order */
        $order = $this->getRecord();

        $refunded = $order->refunded ? ' - (Refunded)' : '';

        return "Order #{$order->id}{$refunded}";
    }

    public function getBreadcrumbs(): array
    {
        $breadcrumbs = parent::getBreadcrumbs();

        // Remove "Order # > View"
        $breadcrumbs = array_slice($breadcrumbs, 0, -2);

        $breadcrumbs[] = 'Order Details';

        return $breadcrumbs;
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->refundAction(),
        ];
    }

    protected function refundAction(): Action
    {
        return Actions\Action::make('refund')
            ->label('Begin Refund')
            // ->url(fn (Order $record): string => RefundOrder::getUrl(['record' => $record->id]))
            // ->modalContent(fn (Order $record): string => view('filament.admin.modals.refund-order-modal', [
            //     'order' => $record,
            // ]))
            // ->form($this->getRefundFormSchema())
            ->requiresConfirmation()
            ->modalHeading(fn (Order $record): string => "Refund Order #{$record->id}")
            ->modalDescription(function (Order $record) {
                if ($record->refundable) {
                    return 'This will refund the order and add the tickets back to the pool.';
                } elseif ($record->refunded) {
                    return 'This order has already been refunded.';
                } else {
                    $ids = $record->purchasedTickets()
                        ->where('user_id', '!=', $record->user_id)
                        ->pluck('id');

                    return 'All tickets in this order must belong to the original user in order to be refunded. ' .
                        Str::plural('Ticket', $ids) . ' #' . $ids->implode(', ') . ' ' . Str::plural('belong', $ids->count() > 1 ? 1 : 2) . ' to a different user.';
                }
            })
            ->modalSubmitAction(fn (Order $record): ?bool => $record->refundable ? null : false)
            ->color('danger')
            ->visible(fn (Order $record) => Auth::authenticate()->can('orders.refund') && $record->refundable)
            ->action(fn (Order $record) => $record->refund());
    }
}
