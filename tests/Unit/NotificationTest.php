<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Ticketing\Order;
use App\Notifications\OrderCompletedNotification;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    #[Test]
    public function order_completed_notification_sent(): void
    {
        Notification::fake();

        Notification::assertNothingSent();

        $order = Order::factory()->create();

        Notification::assertSentTo([$order->user], OrderCompletedNotification::class);
    }
}
