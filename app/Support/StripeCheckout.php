<?php

namespace App\Support;

use App\Exceptions\StripeCheckoutException;
use App\Models\Order;
use Illuminate\Support\Facades\URL;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

class StripeCheckout
{
    public function __construct(private OrderPricing $orderPricing) {}

    /**
     * Create a hosted Checkout Session for an order.
     */
    public function createCheckoutSession(Order $order): string
    {
        try {
            $session = $this->stripe()->checkout->sessions->create([
                'mode' => 'payment',
                'customer_email' => $order->customer_email,
                'client_reference_id' => $order->reference,
                'metadata' => [
                    'order_reference' => $order->reference,
                ],
                'line_items' => [[
                    'price_data' => [
                        'currency' => (string) config('services.stripe.currency'),
                        'product_data' => [
                            'name' => $order->product_name.' × '.$order->quantity,
                        ],
                        'unit_amount' => $this->orderPricing->amountInSen($order->total_price),
                    ],
                    'quantity' => 1,
                ]],
                'success_url' => route('orders.confirmation', $order).'?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => URL::temporarySignedRoute('orders.payment.cancel', now()->addMinutes(30), [
                    'order' => $order,
                ]),
                'expires_at' => (int) now()->addMinutes(30)->timestamp,
            ], [
                'idempotency_key' => $order->reference,
            ]);
        } catch (ApiErrorException $exception) {
            throw new StripeCheckoutException('Unable to create Stripe Checkout Session.', previous: $exception);
        }

        if ($session->url === null) {
            throw new StripeCheckoutException('Stripe Checkout Session is missing its URL.');
        }

        $order->update([
            'stripe_checkout_session_id' => $session->id,
        ]);

        return $session->url;
    }

    /**
     * Expire an open Checkout Session so it cannot be paid after cancellation.
     */
    public function expireCheckoutSession(Order $order): void
    {
        if ($order->stripe_checkout_session_id === null) {
            return;
        }

        try {
            $this->stripe()->checkout->sessions->expire($order->stripe_checkout_session_id);
        } catch (ApiErrorException $exception) {
            throw new StripeCheckoutException('Unable to cancel Stripe Checkout Session.', previous: $exception);
        }
    }

    /**
     * Construct the Stripe client only when an external call is required.
     */
    private function stripe(): StripeClient
    {
        $secret = (string) config('services.stripe.secret');

        if (trim($secret) === '') {
            throw new StripeCheckoutException('Stripe secret key is not configured.');
        }

        return new StripeClient($secret);
    }
}
