<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductDetailResource;
use App\Models\Product;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function show(Product $product): Response
    {
        $product->load(['category', 'variants']);

        return Inertia::render('Product/Show', [
            'product' => (new ProductDetailResource($product))->resolve(),
        ]);
    }
}
