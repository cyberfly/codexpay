<?php

use App\Exceptions\StripeCheckoutException;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\OrderStatus;
use App\PaymentStatus;
use App\Support\StripeCheckout;
use Illuminate\Support\Facades\URL;
use Stripe\WebhookSignature;

use function Pest\Laravel\mock;

function signedStripeWebhook(string $type, Order $order, array $overrides = []): array
{
    $payload = json_encode([
        'id' => 'evt_'.$type,
        'object' => 'event',
        'type' => $type,
        'data' => [
            'object' => [
                'id' => $order->stripe_checkout_session_id,
                'object' => 'checkout.session',
                'currency' => 'myr',
                'amount_total' => (int) str_replace('.', '', $order->total_price),
                'payment_status' => 'paid',
                'payment_intent' => 'pi_'.$order->id,
                ...$overrides,
            ],
        ],
    ], JSON_THROW_ON_ERROR);

    return [
        $payload,
        WebhookSignature::generateSignatureHeader($payload, 'whsec_test'),
    ];
}

function postSignedStripeWebhook($test, string $payload, string $signature)
{
    return $test->call('POST', route('stripe.webhook'), [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_STRIPE_SIGNATURE' => $signature,
    ], $payload);
}

beforeEach(function (): void {
    config()->set('services.stripe.webhook_secret', 'whsec_test');
});

test('redirects a valid product order to a Stripe Checkout Session', function () {
    $product = Product::factory()->create(['price' => '19.95']);
    mock(StripeCheckout::class)
        ->shouldReceive('createCheckoutSession')
        ->once()
        ->withArgs(fn (Order $order): bool => $order->total_price === '19.95')
        ->andReturn('https://checkout.stripe.test/c/pay/cs_test_123');

    $response = $this->post(route('products.orders.store', $product), [
        'customer_name' => 'Aina Ahmad',
        'customer_email' => 'aina@example.com',
        'customer_phone' => '0123456789',
        'quantity' => 1,
        'submission_token' => fake()->uuid(),
    ]);

    $response->assertRedirect('https://checkout.stripe.test/c/pay/cs_test_123');
    $this->assertDatabaseHas('orders', [
        'product_id' => $product->id,
        'payment_status' => PaymentStatus::Pending->value,
    ]);
});

test('removes a pending order and coupon reservation when Checkout cannot be created', function () {
    $product = Product::factory()->create();
    $coupon = Coupon::factory()->create(['code' => 'SAVE10']);
    $user = User::factory()->create(['email' => 'aina@example.com']);
    mock(StripeCheckout::class)
        ->shouldReceive('createCheckoutSession')
        ->once()
        ->andThrow(new StripeCheckoutException('Stripe is unavailable.'));

    $response = $this->actingAs($user)->from(route('products.show', $product))
        ->post(route('products.orders.store', $product), [
            'customer_name' => 'Aina Ahmad',
            'customer_email' => 'aina@example.com',
            'customer_phone' => '0123456789',
            'quantity' => 1,
            'coupon_code' => $coupon->code,
            'submission_token' => fake()->uuid(),
        ]);

    $response
        ->assertRedirect(route('products.show', $product))
        ->assertSessionHasErrors('payment');
    expect(Order::query()->count())->toBe(0);
    expect(CouponRedemption::query()->count())->toBe(0);
});

test('marks a matching completed Checkout Session as paid', function () {
    $order = Order::factory()->create([
        'total_price' => '19.95',
        'stripe_checkout_session_id' => 'cs_test_paid',
    ]);
    [$payload, $signature] = signedStripeWebhook('checkout.session.completed', $order);

    postSignedStripeWebhook($this, $payload, $signature)->assertNoContent();

    $order->refresh();
    expect($order->status)->toBe(OrderStatus::Completed);
    expect($order->payment_status)->toBe(PaymentStatus::Paid);
    expect($order->stripe_payment_intent_id)->toBe('pi_'.$order->id);
    expect($order->paid_at)->not->toBeNull();
});

test('marks an async payment success as paid', function () {
    $order = Order::factory()->create([
        'total_price' => '25.00',
        'stripe_checkout_session_id' => 'cs_test_async_paid',
    ]);
    [$payload, $signature] = signedStripeWebhook('checkout.session.async_payment_succeeded', $order);

    postSignedStripeWebhook($this, $payload, $signature)->assertNoContent();

    expect($order->fresh()->status)->toBe(OrderStatus::Completed);
    expect($order->fresh()->payment_status)->toBe(PaymentStatus::Paid);
});

test('marks a no-cost Checkout Session as paid', function () {
    $order = Order::factory()->create([
        'total_price' => '0.00',
        'stripe_checkout_session_id' => 'cs_test_free',
    ]);
    [$payload, $signature] = signedStripeWebhook('checkout.session.completed', $order, [
        'amount_total' => 0,
        'payment_status' => 'no_payment_required',
        'payment_intent' => null,
    ]);

    postSignedStripeWebhook($this, $payload, $signature)->assertNoContent();

    $order->refresh();
    expect($order->status)->toBe(OrderStatus::Completed);
    expect($order->payment_status)->toBe(PaymentStatus::Paid);
    expect($order->stripe_payment_intent_id)->toBeNull();
});

test('does not accept a mismatched Checkout Session total', function () {
    $order = Order::factory()->create([
        'total_price' => '19.95',
        'stripe_checkout_session_id' => 'cs_test_mismatch',
    ]);
    [$payload, $signature] = signedStripeWebhook('checkout.session.completed', $order, [
        'amount_total' => 1,
    ]);

    postSignedStripeWebhook($this, $payload, $signature)->assertNoContent();

    expect($order->fresh()->payment_status)->toBe(PaymentStatus::Pending);
});

test('releases a coupon reservation after a terminal Stripe payment event', function (string $eventType, PaymentStatus $paymentStatus) {
    $coupon = Coupon::factory()->create();
    $order = Order::factory()->create([
        'stripe_checkout_session_id' => 'cs_test_terminal_'.$paymentStatus->value,
    ]);
    CouponRedemption::factory()->create([
        'coupon_id' => $coupon->id,
        'order_id' => $order->id,
    ]);
    [$payload, $signature] = signedStripeWebhook($eventType, $order, [
        'payment_status' => 'unpaid',
        'payment_intent' => null,
    ]);

    postSignedStripeWebhook($this, $payload, $signature)->assertNoContent();

    expect($order->fresh()->payment_status)->toBe($paymentStatus);
    $this->assertDatabaseMissing('coupon_redemptions', ['order_id' => $order->id]);
})->with([
    'async payment failure' => ['checkout.session.async_payment_failed', PaymentStatus::Failed],
    'session expiry' => ['checkout.session.expired', PaymentStatus::Expired],
]);

test('handles duplicate completed webhook deliveries idempotently', function () {
    $order = Order::factory()->create([
        'stripe_checkout_session_id' => 'cs_test_duplicate',
    ]);
    [$payload, $signature] = signedStripeWebhook('checkout.session.completed', $order);

    postSignedStripeWebhook($this, $payload, $signature)->assertNoContent();
    $paidAt = $order->fresh()->paid_at;
    postSignedStripeWebhook($this, $payload, $signature)->assertNoContent();

    expect($order->fresh()->paid_at)->toEqual($paidAt);
});

test('rejects a Stripe webhook with an invalid signature', function () {
    $order = Order::factory()->create([
        'stripe_checkout_session_id' => 'cs_test_invalid_signature',
    ]);
    [$payload] = signedStripeWebhook('checkout.session.completed', $order);

    postSignedStripeWebhook($this, $payload, 't=1,v1=invalid')->assertStatus(400);

    expect($order->fresh()->payment_status)->toBe(PaymentStatus::Pending);
});

test('cancels a signed pending Checkout Session and releases its coupon', function () {
    $product = Product::factory()->create();
    $coupon = Coupon::factory()->create();
    $order = Order::factory()->for($product)->create([
        'stripe_checkout_session_id' => 'cs_test_cancel',
    ]);
    CouponRedemption::factory()->create([
        'coupon_id' => $coupon->id,
        'order_id' => $order->id,
    ]);
    mock(StripeCheckout::class)
        ->shouldReceive('expireCheckoutSession')
        ->once()
        ->withArgs(fn (Order $cancelledOrder): bool => $cancelledOrder->is($order));
    $url = URL::temporarySignedRoute('orders.payment.cancel', now()->addMinutes(30), [
        'order' => $order,
    ]);

    $this->get($url)
        ->assertRedirectToRoute('products.show', $product)
        ->assertSessionHas('payment_error');

    expect($order->fresh()->payment_status)->toBe(PaymentStatus::Cancelled);
    $this->assertDatabaseMissing('coupon_redemptions', ['order_id' => $order->id]);
});
