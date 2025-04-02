<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\OrderResource\Pages;

use App\Filament\Admin\Resources\OrderResource;
use App\Models\Ticketing\Order;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    public function getTitle(): string|Htmlable
    {
        /** @var Order $order */
        $order = $this->getRecord();

        return "Order #{$order->id}";
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
            // ->action(fn (Order $record): void => $record->refund())
            ->requiresConfirmation()
            ->color('danger');
            // ->icon('heroicon-o-cash');
            // ->visible(fn (Order $record): bool => $record->canRefund());
    }
}
