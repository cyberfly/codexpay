<?php

use App\Mail\OrderSubmitted;
use App\Models\Order;

test('renders the submitted order details', function () {
    $order = Order::factory()->create([
        'reference' => 'TMP-20260912-0001',
        'product_name' => 'Panduan Automasi',
        'quantity' => 2,
        'total_price' => '39.90',
        'customer_name' => 'Aina Ahmad',
    ]);

    $mailable = new OrderSubmitted($order);

    $mailable->assertHasSubject('Tempahan diterima: TMP-20260912-0001');
    $mailable->assertSeeInHtml('Aina Ahmad');
    $mailable->assertSeeInHtml('Panduan Automasi');
    $mailable->assertSeeInHtml('RM 39.90');
    $mailable->assertSeeInText('TMP-20260912-0001');
});
