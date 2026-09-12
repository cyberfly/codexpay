<?php

use App\DiscountType;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\Order;
use App\Models\Product;
use App\OrderStatus;
use Carbon\Carbon;

test('creates a direct guest order for a published product', function () {
    $product = Product::factory()->create([
        'name' => 'Panduan Automasi',
        'price' => '19.95',
    ]);

    $response = $this->post(route('products.orders.store', $product), [
        'customer_name' => 'Aina Ahmad',
        'customer_email' => 'aina@example.com',
        'customer_phone' => '0123456789',
        'quantity' => 2,
        'customer_note' => 'Sila hubungi melalui e-mel.',
    ]);

    $order = Order::query()->firstOrFail();

    $response->assertRedirectToRoute('orders.confirmation', $order);
    $this->assertDatabaseHas('orders', [
        'product_id' => $product->id,
        'product_name' => 'Panduan Automasi',
        'quantity' => 2,
        'customer_name' => 'Aina Ahmad',
        'customer_email' => 'aina@example.com',
        'customer_phone' => '0123456789',
    ]);
    expect($order->unit_price)->toBe('19.95');
    expect($order->total_price)->toBe('39.90');
    expect($order->status)->toBe(OrderStatus::New);
});

test('applies a percentage coupon and records its redemption', function () {
    $product = Product::factory()->create(['price' => '19.95']);
    $coupon = Coupon::factory()->create([
        'code' => 'save10',
        'discount_type' => DiscountType::Percentage,
        'discount_value' => '10.00',
    ]);

    $response = $this->post(route('products.orders.store', $product), [
        'customer_name' => 'Aina Ahmad',
        'customer_email' => 'Aina@Example.com',
        'customer_phone' => '0123456789',
        'quantity' => 2,
        'coupon_code' => 'save10',
    ]);

    $order = Order::query()->firstOrFail();

    $response->assertRedirectToRoute('orders.confirmation', $order);
    expect($order->coupon_id)->toBe($coupon->id);
    expect($order->coupon_code)->toBe('SAVE10');
    expect($order->discount_amount)->toBe('3.99');
    expect($order->total_price)->toBe('35.91');
    $this->assertDatabaseHas('coupon_redemptions', [
        'coupon_id' => $coupon->id,
        'order_id' => $order->id,
        'customer_email' => 'aina@example.com',
    ]);

    $this->get(route('orders.confirmation', $order))
        ->assertSee('Subtotal')
        ->assertSee('SAVE10')
        ->assertSee('RM 35.91');
});

test('previews a coupon-adjusted total without creating an order', function () {
    $product = Product::factory()->create(['price' => '19.95']);
    Coupon::factory()->create([
        'code' => 'SAVE10',
        'discount_type' => DiscountType::Percentage,
        'discount_value' => '10.00',
    ]);

    $this->postJson(route('products.coupons.preview', $product), [
        'coupon_code' => 'save10',
        'quantity' => 2,
    ])
        ->assertOk()
        ->assertJson([
            'coupon_code' => 'SAVE10',
            'subtotal' => '39.90',
            'discount_amount' => '3.99',
            'total_price' => '35.91',
        ]);

    expect(Order::query()->count())->toBe(0);
    expect(CouponRedemption::query()->count())->toBe(0);
});

test('rejects an unavailable coupon during preview', function () {
    $product = Product::factory()->create();
    Coupon::factory()->inactive()->create(['code' => 'INACTIVE']);

    $this->postJson(route('products.coupons.preview', $product), [
        'coupon_code' => 'INACTIVE',
        'quantity' => 1,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'coupon_code' => 'Kod kupon tidak sah atau tidak tersedia.',
        ]);

    expect(Order::query()->count())->toBe(0);
});

test('caps a fixed coupon discount at the order subtotal', function () {
    $product = Product::factory()->create(['price' => '10.00']);
    Coupon::factory()->create([
        'code' => 'RM50',
        'discount_type' => DiscountType::FixedAmount,
        'discount_value' => '50.00',
    ]);

    $this->post(route('products.orders.store', $product), [
        'customer_name' => 'Aina Ahmad',
        'customer_email' => 'aina@example.com',
        'customer_phone' => '0123456789',
        'quantity' => 1,
        'coupon_code' => 'rm50',
    ])->assertRedirect();

    $order = Order::query()->firstOrFail();

    expect($order->discount_amount)->toBe('10.00');
    expect($order->total_price)->toBe('0.00');
});

test('rejects an inactive, expired, or product-ineligible coupon', function () {
    $product = Product::factory()->create();
    $otherProduct = Product::factory()->create();
    $inactiveCoupon = Coupon::factory()->inactive()->create(['code' => 'INACTIVE']);
    $expiredCoupon = Coupon::factory()->expired()->create(['code' => 'EXPIRED']);
    $limitedCoupon = Coupon::factory()->create([
        'code' => 'LIMITED',
        'applies_to_all_products' => false,
    ]);
    $limitedCoupon->products()->attach($otherProduct);

    foreach ([$inactiveCoupon, $expiredCoupon, $limitedCoupon] as $coupon) {
        $this->from(route('products.show', $product))
            ->post(route('products.orders.store', $product), [
                'customer_name' => 'Aina Ahmad',
                'customer_email' => fake()->safeEmail(),
                'customer_phone' => '0123456789',
                'quantity' => 1,
                'coupon_code' => $coupon->code,
            ])
            ->assertRedirect(route('products.show', $product))
            ->assertSessionHasErrors('coupon_code');
    }

    expect(Order::query()->count())->toBe(0);
});

test('locks a coupon to an email even after its order is cancelled', function () {
    $product = Product::factory()->create();
    $coupon = Coupon::factory()->create(['code' => 'ONEEMAIL']);
    $payload = [
        'customer_name' => 'Aina Ahmad',
        'customer_email' => 'aina@example.com',
        'customer_phone' => '0123456789',
        'quantity' => 1,
        'coupon_code' => $coupon->code,
    ];

    $this->post(route('products.orders.store', $product), $payload)->assertRedirect();
    Order::query()->firstOrFail()->update(['status' => OrderStatus::Cancelled]);

    $this->from(route('products.show', $product))
        ->post(route('products.orders.store', $product), $payload)
        ->assertRedirect(route('products.show', $product))
        ->assertSessionHasErrors('coupon_code');

    $this->post(route('products.orders.store', $product), [
        ...$payload,
        'customer_email' => 'customer-lain@example.com',
    ])->assertRedirect();

    expect(Order::query()->count())->toBe(2);
    expect(CouponRedemption::query()->where('coupon_id', $coupon->id)->count())->toBe(2);
});

test('expires a coupon after the final second of its Malaysia expiry date', function () {
    $product = Product::factory()->create(['price' => '10.00']);
    $coupon = Coupon::factory()->create([
        'code' => 'LASTDAY',
        'expires_at' => Carbon::create(2026, 9, 12, 23, 59, 59, 'Asia/Kuala_Lumpur')->utc(),
    ]);
    $payload = [
        'customer_name' => 'Aina Ahmad',
        'customer_email' => 'aina@example.com',
        'customer_phone' => '0123456789',
        'quantity' => 1,
        'coupon_code' => $coupon->code,
    ];

    $this->travelTo(Carbon::create(2026, 9, 12, 23, 59, 59, 'Asia/Kuala_Lumpur'));
    $this->post(route('products.orders.store', $product), $payload)->assertRedirect();

    $this->travelTo(Carbon::create(2026, 9, 13, 0, 0, 0, 'Asia/Kuala_Lumpur'));
    $this->from(route('products.show', $product))
        ->post(route('products.orders.store', $product), [
            ...$payload,
            'customer_email' => 'lain@example.com',
        ])
        ->assertRedirect(route('products.show', $product))
        ->assertSessionHasErrors('coupon_code');

    $this->travelBack();
});

test('rejects an incomplete order form', function () {
    $product = Product::factory()->create();

    $response = $this->from(route('products.show', $product))
        ->post(route('products.orders.store', $product), []);

    $response
        ->assertRedirect(route('products.show', $product))
        ->assertSessionHasErrors([
            'customer_name',
            'customer_email',
            'customer_phone',
            'quantity',
        ]);
    expect(Order::query()->count())->toBe(0);
});

test('returns 404 when an unpublished product is ordered', function () {
    $product = Product::factory()->unpublished()->create();

    $this->post(route('products.orders.store', $product), [
        'customer_name' => 'Aina Ahmad',
        'customer_email' => 'aina@example.com',
        'customer_phone' => '0123456789',
        'quantity' => 1,
    ])->assertNotFound();

    expect(Order::query()->count())->toBe(0);
});
