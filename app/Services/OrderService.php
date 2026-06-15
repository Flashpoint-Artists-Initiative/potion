<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Ticketing\Cart;
use App\Models\Ticketing\CartItem;
use App\Models\Ticketing\Order;
use App\Models\Ticketing\TicketType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Stripe\Checkout\Session;

class OrderService
{
    public function __construct(protected StripeService $stripeService) {}

    public function assertSessionDoesntHaveOrder(string $sessionId): void
    {
        abort_if(Order::query()->stripeCheckoutId($sessionId)->exists(), 422, 'Checkout session already processed');
    }

    public function createOrderFromSession(Session $session): Order
    {
        $this->assertSessionDoesntHaveOrder($session->id);
        $cart = Cart::query()->stripeCheckoutId($session->id)->firstOrFail();
        $data = array_merge(
            $this->mapDataFromSession($session),
            $this->mapDataFromCart($cart),
        );

        $order = Order::create($data);

        $this->stripeService->updateMetadata($session, ['order_id' => $order->id]);

        return $order;
    }

    /**
     * @return array<string, string|int|null>
     */
    protected function mapDataFromSession(Session $session): array
    {
        return [
            'user_email' => $session->customer_email,
            'amount_total' => $session->amount_total,
            'stripe_checkout_id' => $session->id,
        ];
    }

    /**
     * @return array<string, string|int|array<string, string|int>>
     */
    protected function mapDataFromCart(Cart $cart): array
    {
        return [
            'user_id' => $cart->user_id,
            'event_id' => $cart->event->id,
            'cart_id' => $cart->id,
            'quantity' => $cart->quantity,
            'ticket_data' => $this->jsonFromCartItems($cart->items),
            'amount_subtotal' => $cart->subtotal,
            'amount_tax' => $cart->taxesOwed,
            'amount_fees' => $cart->feesOwed,
        ];
    }

    /**
     * @param  Collection<int, CartItem>  $items
     * @return array<string, string|int>
     */
    protected function jsonFromCartItems(Collection $items): array
    {
        return $items->each->setVisible([
            'id',
            'ticket_type_id',
            'reserved_ticket_id',
            'quantity',
        ])->toArray();
    }

    /**
     * Gets various stats and totals for either the given event or everything
     *
     * @return array<string, mixed>
     */
    public function getSalesData(int $eventId): array
    {
        $orders = Order::where('event_id', $eventId)->get();

        return [
            'totals' => [
                'gross_profit' => $orders->sum('amount_total') / 100,
                'net_profit' => $orders->sum('amount_subtotal') / 100,
                'taxes_collected' => $orders->sum('amount_tax') / 100,
                'fees_collected' => $orders->sum('amount_fees') / 100,
                'tickets_sold' => $orders->sum('quantity'),
                'orders_count' => $orders->count(),
            ],
            'ticket_breakdown' => $this->getTicketTypeSaleDataFromOrders($orders),
            'sales_histogram' => $this->getHistoryDataFromOrders($orders),
        ];
    }

    /**
     * Separates a collection of Orders into sale data broken down by ticket type
     * Includes ticket type name and id for reference and linking
     *
     * @param  Collection<int, Order>  $orders
     * @return array<int, array<string, mixed>> Sale data for each ticket type
     */
    protected function getTicketTypeSaleDataFromOrders(Collection $orders): array
    {
        $quantites = [];

        foreach ($orders as $order) {
            foreach ($order->ticket_data as $item) {
                if (! array_key_exists($item['ticket_type_id'], $quantites)) {
                    $quantites[$item['ticket_type_id']] = 0;
                }

                $quantites[$item['ticket_type_id']] += $item['quantity'];
            }
        }

        $ticketTypes = TicketType::whereIn('id', array_keys($quantites))->get();
        $output = [];

        foreach ($ticketTypes as $type) {
            $output[] = [
                'id' => $type->id,
                'name' => $type->name,
                'quantity' => $quantites[$type->id],
                'profit' => $type->price * $quantites[$type->id],
            ];
        }

        return $output;
    }

    /**
     * Separates a collection of Orders into historical data broken down by date
     *
     * @param  Collection<int, Order>  $orders
     * @return array<int, array<string, int|string>> Sale data for each ticket type
     */
    protected function getHistoryDataFromOrders(Collection $orders): array
    {
        $output = [];

        foreach ($orders as $order) {
            $createdAt = $order->created_at->format('n/j/y');

            if (! array_key_exists($createdAt, $output)) {
                $output[$createdAt] = [
                    'date' => $createdAt,
                    'orders' => 0,
                    'tickets' => 0,
                ];
            }

            $output[$createdAt]['orders']++;
            $output[$createdAt]['tickets'] += $order->quantity;
        }

        uksort($output, fn ($a, $b) => new Carbon($a) > new Carbon($b) ? 1 : -1);

        return array_values($output);
    }
}
