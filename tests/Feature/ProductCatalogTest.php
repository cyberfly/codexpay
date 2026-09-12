<?php

use App\Models\Product;

test('lists only published products in the catalogue', function () {
    $publishedProduct = Product::factory()->create(['name' => 'Panduan Laravel']);
    $unpublishedProduct = Product::factory()->unpublished()->create(['name' => 'Produk Draf']);

    $response = $this->get(route('home'));

    $response
        ->assertSee($publishedProduct->name)
        ->assertDontSee($unpublishedProduct->name);
});

test('lists only published products on the products page', function () {
    $publishedProduct = Product::factory()->create(['name' => 'Produk Diterbitkan']);
    $unpublishedProduct = Product::factory()->unpublished()->create(['name' => 'Produk Tersembunyi']);

    $response = $this->get(route('products.index'));

    $response
        ->assertSee($publishedProduct->name)
        ->assertDontSee($unpublishedProduct->name);
});

test('renders a published product page', function () {
    $product = Product::factory()->create(['name' => 'Kit Reka Bentuk']);

    $response = $this->get(route('products.show', $product));

    $response
        ->assertSee($product->name)
        ->assertSee('Borang tempahan')
        ->assertSee('Guna kupon')
        ->assertSee('Jumlah akhir');
});

test('renders supported rich text and removes unsafe product description markup', function () {
    $product = Product::factory()->create([
        'description' => '<h2>Tajuk panduan</h2><p><strong>Isi selamat</strong><script>alert(1)</script></p>',
    ]);

    $response = $this->get(route('products.show', $product));

    $response
        ->assertSee('<h2>Tajuk panduan</h2>', false)
        ->assertSee('<strong>Isi selamat</strong>', false)
        ->assertDontSee('<script>alert(1)</script>', false);
});

test('returns 404 for an unpublished product page', function () {
    $product = Product::factory()->unpublished()->create();

    $this->get(route('products.show', $product))->assertNotFound();
});
