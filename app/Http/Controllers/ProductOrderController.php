<?php

namespace App\Http\Controllers;

use App\Actions\CreateProductOrder;
use App\Http\Requests\PreviewCouponRequest;
use App\Http\Requests\StoreOrderRequest;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Support\OrderPricing;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductOrderController extends Controller
{
    /**
     * Store an order for a single published product.
     */
    public function store(StoreOrderRequest $request, Product $product, CreateProductOrder $createProductOrder): RedirectResponse
    {
        abort_unless($product->is_published, 404);

        $order = $createProductOrder->handle(
            $product,
            $request->integer('quantity'),
            $request->validated(),
        );

        return to_route('orders.confirmation', $order);
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
