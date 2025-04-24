<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Ticketing\Cart;
use App\Models\Ticketing\CartItem;
use App\Models\Ticketing\Order;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\InvalidRequestException;
use Stripe\Refund;
use Stripe\StripeClient;

/**
 * @mixin StripeClient
 */
class StripeService
{
    const TAX_RATE_CACHE_PREFIX = 'stripe.tax-rate.';

    public function __construct(public StripeClient $stripeClient) {}

    // These magic methods redirect all calls to the $stripeClient
    public function __get(mixed $name): mixed
    {
        return $this->stripeClient->$name;
    }

    public function __call(mixed $name, mixed $arguments): mixed
    {
        return $this->stripeClient->$name($arguments);
    }

    public function getCheckoutSession(string $id): Session
    {
        try {
            return $this->stripeClient->checkout->sessions->retrieve($id);
        } catch (InvalidRequestException $e) {
            abort(422, 'Invalid Stripe checkout session id');
        }
    }

    public function refundOrder(Order $order): Refund
    {
        if ($order->refunded) {
            abort(422, 'Order has already been refunded');
        }

        $session = $this->getCheckoutSession($order->stripe_checkout_id);

        try {
            // @phpstan-ignore argument.type
            $refund = $this->stripeClient->refunds->create([
                'payment_intent' => (string) $session->payment_intent,
                'amount' => $order->amount_total,
                'reason' => 'requested_by_customer',
                'metadata' => [
                    'order_id' => $order->id,
                    'user_id' => $order->user_id,
                ],
            ]);
        } catch (ApiErrorException $e) {
            abort(422, 'Refund failed: ' . $e->getMessage());
        }

        return $refund;
    }

    public function createCheckoutFromCart(Cart $cart): Session
    {
        $client_reference_id = sprintf('Event: %s - User: %d - Cart: %d', $cart->event->name, $cart->user_id, $cart->id);
        $user = Auth::user();
        abort_unless($user instanceof User, 400, 'User not found');

        // @phpstan-ignore argument.type
        $checkout_session = $this->stripeClient->checkout->sessions->create([
            'redirect_on_completion' => 'never',
            'payment_method_configuration' => config('services.stripe.payment_method_configuration'),
            'mode' => 'payment',
            'ui_mode' => 'embedded',
            'customer_email' => $user->email,
            'client_reference_id' => $client_reference_id,
            'customer_creation' => 'if_required',
            'line_items' => $this->buildLineItems($cart),
            'expires_at' => (int) now()->addMinutes(31)->format('U'),
            'metadata' => [
                'event_id' => $cart->event->id,
                'user_id' => $cart->user_id,
                'ticket_quantity' => $cart->quantity,
            ],
            'payment_intent_data' => [
                'metadata' => [
                    'event_id' => $cart->event->id,
                    'user_id' => $cart->user_id,
                    'ticket_quantity' => $cart->quantity,
                ],
            ],
            'custom_text' => [
                'submit' => [
                    'message' => "You'll receive an email confirmation with your ticket information.",
                ],
            ],
        ]);

        return $checkout_session;
    }

    /**
     * Update the metadata for a checkout session and its payment intent
     *
     * @param  array<string,string|int>  $metadata
     */
    public function updateMetadata(Session $session, array $metadata): Session
    {
        // @phpstan-ignore argument.type
        $session = $this->stripeClient->checkout->sessions->update($session->id, [
            'metadata' => $metadata,
        ]);

        if ($session->payment_intent) {
            // @phpstan-ignore argument.type
            $this->stripeClient->paymentIntents->update((string) $session->payment_intent, [
                'metadata' => $metadata,
            ]);
        }

        return $session;
    }

    public function expireCheckoutFromCart(Cart $cart): void
    {
        if ($cart->stripe_checkout_id) {
            try {
                $this->stripeClient->checkout->sessions->expire($cart->stripe_checkout_id);
            } catch (ApiErrorException $e) {  // Occurs when the session is already expired by Stripe
            }
        }
    }

    /**
     * Create the line_items array for a checkout session from the cart's items
     *
     * @return array<string, mixed>
     */
    protected function buildLineItems(Cart $cart): array
    {
        $subtotal = 0;

        // Add ticket line items
        $output = $cart->items->map(function (CartItem $item) use (&$subtotal) {
            $subtotal += $item->ticketType->price * $item->quantity * 100; // Stripe price is in cents

            return [
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => $item->ticketType->name,
                        'metadata' => [
                            'ticket_type_id' => $item->ticketType->id,
                            'type' => 'ticket',
                        ],
                    ],
                    'unit_amount' => $item->ticketType->price * 100, // Stripe price is in cents
                ],
                'quantity' => $item->quantity,
            ];
        })->toArray();

        if ($subtotal > 0) {
            $taxAndFees = $this->calculateTaxesAndFees($subtotal);

            // Add Tax
            $output[] = [
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => 'GA Sales Tax',
                        'metadata' => [
                            'type' => 'tax',
                        ],
                    ],
                    'unit_amount' => $taxAndFees['tax'],
                ],
                'quantity' => 1,
            ];

            // Add stripe fee
            $output[] = [
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => 'Stripe Fee',
                        'metadata' => [
                            'type' => 'fee',
                        ],
                    ],
                    'unit_amount' => $taxAndFees['fees'],
                ],
                'quantity' => 1,
            ];
        }

        return $output;
    }

    /**
     * Calculate fees and taxes for a given amount
     *
     * We need to do both at the same time because fees and taxes are both calculated based on each other.
     * Fortunately, iterating over both until they stabilize only takes a few iterations.
     *
     * @param  int  $amount  The amount in cents
     * @return array{'tax':int,'fees':int} The calculated tax and fees
     */
    public function calculateTaxesAndFees(int $amount): array
    {
        if ($amount === 0) {
            return [
                'tax' => 0,
                'fees' => 0,
            ];
        }
        
        // These functions live inside so they don't accidentally get called from outside
        $calculateSalesTax = function (int $amount): int {
            $taxRate = config('services.stripe.sales_tax_rate'); // Config value is a percentage (0-100)

            return (int) round($amount * ($taxRate / 100));
        };

        $calculateStripeFee = function (int $amount): int {
            $feePercentage = config('services.stripe.stripe_fee_percentage'); // percentage (0-100)
            $feeFlat = config('services.stripe.stripe_fee_flat'); // flat fee in cents

            // We calculate what the final amount would be after Stripe fees, then remove the original amount
            // Trying to doing it the other way around (calculating the fee) results in rounding errors
            $totalWithStripe = (int) round(($amount + $feeFlat) / (1 - ($feePercentage / 100)));

            return $totalWithStripe - $amount;
        };

        $tax = 0;
        $fees = 0;

        for ($i = 1; $i <= 10; $i++) {
            $prevTax = $tax;
            $prevFees = $fees;

            $tax = $calculateSalesTax($amount + $fees);
            $fees = $calculateStripeFee($amount + $tax);

            if ($tax === $prevTax && $fees === $prevFees) {
                break; // Stop if the values don't change anymore
            }
        }

        return [
            'tax' => $tax,
            'fees' => $fees,
        ];
    }

    public function assertSessionIsPaid(Session $session): void
    {
        abort_if($session->status !== 'complete', 422, 'Session has not been completed');
        abort_if($session->payment_status !== 'paid', 422, 'Session payment is not complete');
    }
}
