<?php

namespace App\Actions;

use App\Models\Order;
use App\OrderStatus;
use App\PaymentStatus;
use App\Support\OrderPricing;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Checkout\Session;
use Stripe\Event;

class ProcessStripeWebhook
{
    public function __construct(private OrderPricing $orderPricing) {}

    /**
     * Apply a verified Stripe Checkout event to its matching order.
     */
    public function handle(Event $event): void
    {
        if (! in_array($event->type, [
            'checkout.session.completed',
            'checkout.session.async_payment_succeeded',
            'checkout.session.async_payment_failed',
            'checkout.session.expired',
        ], true)) {
            return;
        }

        $session = Session::constructFrom($event->data->object->toArray());

        DB::transaction(function () use ($event, $session): void {
            $order = Order::query()
                ->where('stripe_checkout_session_id', $session->id)
                ->lockForUpdate()
                ->first();

            if ($order === null) {
                Log::warning('Stripe Checkout Session does not match a local order.', [
                    'stripe_checkout_session_id' => $session->id,
                    'stripe_event_id' => $event->id,
                    'stripe_event_type' => $event->type,
                ]);

                return;
            }

            if (in_array($event->type, [
                'checkout.session.completed',
                'checkout.session.async_payment_succeeded',
            ], true)) {
                if (! $this->sessionIsPaid($session)) {
                    return;
                }

                if (! $this->sessionMatchesOrder($session, $order)) {
                    Log::warning('Stripe Checkout Session total does not match the local order.', [
                        'order_reference' => $order->reference,
                        'stripe_checkout_session_id' => $session->id,
                        'stripe_currency' => $session->currency,
                        'stripe_amount_total' => $session->amount_total,
                    ]);

                    return;
                }

                if ($order->hasConfirmedPayment()) {
                    return;
                }

                $order->update([
                    'status' => OrderStatus::Completed,
                    'payment_status' => PaymentStatus::Paid,
                    'stripe_payment_intent_id' => $this->paymentIntentId($session),
                    'paid_at' => now(),
                ]);

                return;
            }

            $this->markPaymentAsTerminal($order, match ($event->type) {
                'checkout.session.async_payment_failed' => PaymentStatus::Failed,
                'checkout.session.expired' => PaymentStatus::Expired,
            });
        });
    }

    /**
     * Cancel a pending order after its Checkout Session has been expired.
     */
    public function cancel(Order $order): void
    {
        DB::transaction(function () use ($order): void {
            $lockedOrder = Order::query()
                ->whereKey($order)
                ->lockForUpdate()
                ->firstOrFail();

            $this->markPaymentAsTerminal($lockedOrder, PaymentStatus::Cancelled);
        });
    }

    /**
     * Mark an order as paid only when Checkout confirms it.
     */
    private function sessionIsPaid(Session $session): bool
    {
        return in_array($session->payment_status, ['paid', 'no_payment_required'], true);
    }

    /**
     * Ensure the server-calculated order total is the amount Stripe settled.
     */
    private function sessionMatchesOrder(Session $session, Order $order): bool
    {
        return $session->currency === config('services.stripe.currency')
            && $session->amount_total === $this->orderPricing->amountInSen($order->total_price);
    }

    /**
     * Resolve the optional PaymentIntent identifier from a Checkout Session.
     */
    private function paymentIntentId(Session $session): ?string
    {
        if (is_string($session->payment_intent)) {
            return $session->payment_intent;
        }

        if ($session->payment_intent !== null) {
            return $session->payment_intent->id;
        }

        return null;
    }

    /**
     * Release a reserved coupon when its payment cannot be completed.
     */
    private function markPaymentAsTerminal(Order $order, PaymentStatus $paymentStatus): void
    {
        if ($order->hasConfirmedPayment()) {
            return;
        }

        $order->update([
            'payment_status' => $paymentStatus,
        ]);

        $order->couponRedemption()->delete();
    }
}
