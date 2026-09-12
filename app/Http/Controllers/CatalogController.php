<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Contracts\View\View;

class CatalogController extends Controller
{
    /**
     * Show the published product catalogue.
     */
    public function index(): View
    {
        return view('welcome', [
            'products' => Product::query()
                ->published()
                ->latest()
                ->paginate(12),
        ]);
    }

    /**
     * Show the full published product catalogue.
     */
    public function products(): View
    {
        return view('products.index', [
            'products' => Product::query()
                ->published()
                ->latest()
                ->paginate(12),
        ]);
    }

    /**
     * Show a published product and its direct order form.
     */
    public function show(Product $product): View
    {
        abort_unless($product->is_published, 404);

        return view('products.show', [
            'product' => $product,
        ]);
    }
}
