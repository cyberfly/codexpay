<?php

namespace App\Http\Controllers;

use App\Actions\ProcessStripeWebhook;
use App\Exceptions\StripeCheckoutException;
use App\Models\Order;
use App\PaymentStatus;
use App\Support\StripeCheckout;
use Illuminate\Http\RedirectResponse;

class OrderPaymentController extends Controller
{
    /**
     * Expire a signed Checkout Session and release its coupon reservation.
     */
    public function cancel(
        Order $order,
        StripeCheckout $stripeCheckout,
        ProcessStripeWebhook $processStripeWebhook,
    ): RedirectResponse {
        if ($order->hasConfirmedPayment() || $order->getRawOriginal('payment_status') !== PaymentStatus::Pending->value) {
            return to_route('products.show', $order->product);
        }

        try {
            $stripeCheckout->expireCheckoutSession($order);
        } catch (StripeCheckoutException $exception) {
            report($exception);

            return to_route('products.show', $order->product)
                ->with('payment_error', 'Pembayaran tidak dapat dibatalkan. Sila cuba lagi sebentar.');
        }

        $processStripeWebhook->cancel($order);

        return to_route('products.show', $order->product)
            ->with('payment_error', 'Pembayaran dibatalkan. Kupon anda boleh digunakan semula.');
    }
}
