<?php

namespace App\Http\Controllers;

use App\Actions\CreateProductOrder;
use App\Exceptions\StripeCheckoutException;
use App\Http\Requests\PreviewCouponRequest;
use App\Http\Requests\StoreOrderRequest;
use App\Mail\AdminOrderSubmitted;
use App\Mail\OrderSubmitted;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Support\OrderPricing;
use App\Support\StripeCheckout;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class ProductOrderController extends Controller
{
    /**
     * Store an order for a single published product.
     */
    public function store(
        StoreOrderRequest $request,
        Product $product,
        CreateProductOrder $createProductOrder,
        StripeCheckout $stripeCheckout,
    ): RedirectResponse {
        abort_unless($product->is_published, 404);

        $couponCode = trim($request->string('coupon_code')->toString());

        $order = $createProductOrder->handle(
            $product,
            $request->integer('quantity'),
            $request->string('submission_token')->toString(),
            [
                'customer_name' => $request->string('customer_name')->toString(),
                'customer_email' => $couponCode === ''
                    ? $request->string('customer_email')->toString()
                    : $request->user()->email,
                'customer_phone' => $request->string('customer_phone')->toString(),
                'customer_note' => $request->filled('customer_note')
                    ? $request->string('customer_note')->toString()
                    : null,
                'coupon_code' => $couponCode === '' ? null : $couponCode,
            ],
        );

        try {
            $checkoutUrl = $stripeCheckout->createCheckoutSession($order);
        } catch (StripeCheckoutException $exception) {
            if ($order->wasRecentlyCreated) {
                $order->delete();
            }

            report($exception);

            return back()
                ->withInput()
                ->withErrors(['payment' => 'Pembayaran tidak dapat dimulakan. Sila cuba lagi.']);
        }

        if ($order->wasRecentlyCreated) {
            try {
                Mail::to($order->customer_email)->send(new OrderSubmitted($order));
            } catch (Throwable $exception) {
                report($exception);
            }

            $administrators = User::query()->where('is_admin', true)->get();

            foreach ($administrators as $administrator) {
                try {
                    Mail::to($administrator)->send(new AdminOrderSubmitted($order));
                } catch (Throwable $exception) {
                    report($exception);
                }
            }
        }

        return redirect()->away($checkoutUrl);
    }

    /**
     * Return a coupon-adjusted order total before submission.
     */
    public function previewCoupon(PreviewCouponRequest $request, Product $product, OrderPricing $orderPricing): JsonResponse
    {
        abort_unless($product->is_published, 404);

        $validated = $request->validated();
        $coupon = Coupon::query()
            ->where('code', Str::upper(trim($validated['coupon_code'])))
            ->first();

        if ($coupon === null || ! $coupon->isAvailableFor($product)) {
            throw ValidationException::withMessages([
                'coupon_code' => 'Kod kupon tidak sah atau tidak tersedia.',
            ]);
        }

        return response()->json([
            'coupon_code' => $coupon->code,
            ...$orderPricing->quote($product, $validated['quantity'], $coupon),
        ]);
    }

    /**
     * Show the confirmation for a submitted order.
     */
    public function confirmation(Order $order): View
    {
        return view('orders.confirmation', [
            'order' => $order,
        ]);
    }
}
