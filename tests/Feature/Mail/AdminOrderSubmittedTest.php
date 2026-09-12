<?php

use App\Mail\AdminOrderSubmitted;
use App\Models\Order;

test('renders the new order details for an administrator', function () {
    $order = Order::factory()->create([
        'reference' => 'TMP-20260912-0002',
        'product_name' => 'Panduan Automasi',
        'quantity' => 2,
        'total_price' => '39.90',
        'customer_name' => 'Aina Ahmad',
        'customer_email' => 'aina@example.com',
        'customer_phone' => '0123456789',
        'customer_note' => 'Sila hubungi melalui e-mel.',
    ]);

    $mailable = new AdminOrderSubmitted($order);

    $mailable->assertHasSubject('Tempahan baharu: TMP-20260912-0002');
    $mailable->assertSeeInHtml('aina@example.com');
    $mailable->assertSeeInHtml('0123456789');
    $mailable->assertSeeInHtml('Sila hubungi melalui e-mel.');
    $mailable->assertSeeInText('TMP-20260912-0002');
});
