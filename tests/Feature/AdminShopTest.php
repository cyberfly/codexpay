<?php

use App\DiscountType;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\OrderStatus;
use App\PaymentStatus;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('guests are redirected to the login page for admin shop pages', function () {
    $this->get(route('admin.products'))->assertRedirect(route('login'));
    $this->get(route('admin.coupons'))->assertRedirect(route('login'));
});

test('returns 403 for a non-admin user visiting admin shop pages', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.products'))
        ->assertForbidden();
    $this->actingAs($user)
        ->get(route('admin.coupons'))
        ->assertForbidden();
});

test('renders product and order management pages for an admin', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $this->get(route('admin.products'))
        ->assertSee('Tambah produk');
    $this->get(route('admin.orders'))
        ->assertSee('Semak pesanan');
    $this->get(route('admin.coupons'))
        ->assertSee('Tambah kupon');
});

test('allows an admin to create a product-scoped coupon', function () {
    $admin = User::factory()->admin()->create();
    $product = Product::factory()->create();
    $this->actingAs($admin);

    Livewire::test('pages::admin.coupons')
        ->set('code', 'launch15')
        ->set('discountType', DiscountType::Percentage->value)
        ->set('discountValue', '15.00')
        ->set('appliesToAllProducts', false)
        ->set('productIds', [$product->id])
        ->set('expiresAt', today()->addWeek()->toDateString())
        ->call('save')
        ->assertHasNoErrors();

    $coupon = Coupon::query()->firstOrFail();

    expect($coupon->code)->toBe('LAUNCH15');
    expect($coupon->discount_type)->toBe(DiscountType::Percentage);
    expect($coupon->applies_to_all_products)->toBeFalse();
    expect($coupon->expires_at?->format('H:i:s'))->toBe('23:59:59');
    expect($coupon->products->modelKeys())->toBe([$product->id]);
});

test('allows an admin to deactivate a redeemed coupon but deletes an unused coupon', function () {
    $admin = User::factory()->admin()->create();
    $redeemedCoupon = Coupon::factory()->create();
    $order = Order::factory()->create(['coupon_id' => $redeemedCoupon->id]);
    CouponRedemption::factory()->create([
        'coupon_id' => $redeemedCoupon->id,
        'order_id' => $order->id,
    ]);
    $unusedCoupon = Coupon::factory()->create();
    $this->actingAs($admin);

    Livewire::test('pages::admin.coupons')
        ->call('deleteCoupon', $redeemedCoupon->id)
        ->assertHasNoErrors();
    Livewire::test('pages::admin.coupons')
        ->call('deleteCoupon', $unusedCoupon->id)
        ->assertHasNoErrors();

    expect($redeemedCoupon->fresh()->is_active)->toBeFalse();
    $this->assertDatabaseMissing('coupons', ['id' => $unusedCoupon->id]);
});

test('allows an admin to create a product', function () {
    $admin = User::factory()->admin()->create();
    Storage::fake('public');
    $this->actingAs($admin);

    Livewire::test('pages::admin.products')
        ->set('name', 'Template Perniagaan')
        ->set('slug', 'template-perniagaan')
        ->set('description', 'Template digital untuk perniagaan kecil.')
        ->set('price', '35.00')
        ->set('isPublished', true)
        ->set('image', UploadedFile::fake()->image('template.png'))
        ->call('save')
        ->assertHasNoErrors();

    $product = Product::query()->firstOrFail();

    $this->assertDatabaseHas('products', [
        'name' => 'Template Perniagaan',
        'slug' => 'template-perniagaan',
        'is_published' => true,
    ]);
    Storage::disk('public')->assertExists($product->image_path);
});

test('opens a product in the edit form', function () {
    $admin = User::factory()->admin()->create();
    $product = Product::factory()->create();
    $this->actingAs($admin);

    Livewire::test('pages::admin.products')
        ->call('edit', $product->id)
        ->assertSet('editingProductId', $product->id)
        ->assertSet('name', $product->name)
        ->assertSet('showForm', true)
        ->assertDispatched('product-description-loaded', description: $product->description);
});

test('filters products by name or slug', function () {
    $admin = User::factory()->admin()->create();
    $nameMatch = Product::factory()->create([
        'name' => 'Template Perniagaan',
        'slug' => 'template-perniagaan',
    ]);
    $slugMatch = Product::factory()->create([
        'name' => 'Panduan Asas',
        'slug' => 'panduan-tiptap',
    ]);
    $unmatchedProduct = Product::factory()->create(['name' => 'Kit Reka Bentuk']);
    $this->actingAs($admin);

    $component = Livewire::test('pages::admin.products')
        ->set('search', 'template');

    $component
        ->assertSee($nameMatch->name)
        ->assertDontSee($slugMatch->name)
        ->assertDontSee($unmatchedProduct->name);

    $component
        ->set('search', $slugMatch->slug)
        ->assertSee($slugMatch->name)
        ->assertDontSee($nameMatch->name)
        ->assertDontSee($unmatchedProduct->name);
});

test('sanitizes rich-text product descriptions created by an admin', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    Livewire::test('pages::admin.products')
        ->set('name', 'Panduan Tiptap')
        ->set('slug', 'panduan-tiptap')
        ->set('description', '<h2 onclick="alert(1)">Tajuk panduan</h2><p><strong>Isi selamat</strong><script>alert(1)</script></p>')
        ->set('price', '35.00')
        ->call('save')
        ->assertHasNoErrors();

    $product = Product::query()->firstOrFail();

    expect($product->description)
        ->toContain('<h2>Tajuk panduan</h2>')
        ->toContain('<strong>Isi selamat</strong>')
        ->not->toContain('onclick')
        ->not->toContain('<script>');
});

test('allows an admin to update the fulfilment status of a paid order', function () {
    $admin = User::factory()->admin()->create();
    $order = Order::factory()->create(['payment_status' => PaymentStatus::Paid]);
    $this->actingAs($admin);

    Livewire::test('pages::admin.orders')
        ->call('updateStatus', $order->reference, OrderStatus::Completed->value)
        ->assertHasNoErrors();

    expect($order->fresh()->status)->toBe(OrderStatus::Completed);
});

test('prevents an admin from updating the fulfilment status of an unpaid order', function () {
    $admin = User::factory()->admin()->create();
    $order = Order::factory()->create(['payment_status' => PaymentStatus::Pending]);
    $this->actingAs($admin);

    Livewire::test('pages::admin.orders')
        ->call('updateStatus', $order->reference, OrderStatus::Completed->value)
        ->assertHasErrors(['status']);

    expect($order->fresh()->status)->toBe(OrderStatus::New);
});

test('renders an order detail page for an admin', function () {
    $admin = User::factory()->admin()->create();
    $order = Order::factory()->create([
        'customer_name' => 'Aina Ahmad',
        'customer_note' => 'Hantar melalui e-mel.',
        'payment_status' => PaymentStatus::Paid,
        'stripe_checkout_session_id' => 'cs_test_123',
        'stripe_payment_intent_id' => 'pi_test_123',
        'paid_at' => now(),
    ]);
    $this->actingAs($admin);

    $this->get(route('admin.orders.show', $order))
        ->assertSee($order->reference)
        ->assertSee('Aina Ahmad')
        ->assertSee('Hantar melalui e-mel.')
        ->assertSee('Stripe Checkout Session')
        ->assertSee('cs_test_123')
        ->assertSee('Stripe PaymentIntent')
        ->assertSee('pi_test_123');
});

test('allows an admin to update the fulfilment status of a paid order from its detail page', function () {
    $admin = User::factory()->admin()->create();
    $order = Order::factory()->create(['payment_status' => PaymentStatus::Paid]);
    $this->actingAs($admin);

    Livewire::test('pages::admin.order', ['order' => $order])
        ->set('status', OrderStatus::Processing->value)
        ->call('updateStatus')
        ->assertHasNoErrors();

    expect($order->fresh()->status)->toBe(OrderStatus::Processing);
});

test('renders a sales chart that only includes paid orders', function () {
    $admin = User::factory()->admin()->create();
    Order::factory()->create([
        'total_price' => '50.00',
        'status' => OrderStatus::Completed,
        'payment_status' => PaymentStatus::Paid,
        'created_at' => today(),
    ]);
    Order::factory()->create([
        'total_price' => '100.00',
        'status' => OrderStatus::Cancelled,
        'payment_status' => PaymentStatus::Pending,
        'created_at' => today(),
    ]);
    $this->actingAs($admin);

    $component = Livewire::test('pages::admin.dashboard');
    $salesChart = $component->get('salesChart');

    $component->assertSee('Jualan 30 hari terakhir');
    expect($salesChart['totals'])->toContain(50.0)->not->toContain(100.0);
});
