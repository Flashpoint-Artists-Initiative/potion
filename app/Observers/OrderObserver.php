<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Ticketing\Order;
use App\Notifications\OrderCompletedNotification;

class OrderObserver
{
    /**
     * Handle the Order "created" event.
     */
    public function created(Order $order): void
    {
        $order->user->notify(new OrderCompletedNotification($order));
    }
}
