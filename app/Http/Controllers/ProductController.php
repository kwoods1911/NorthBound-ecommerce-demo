<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductDetailResource;
use App\Models\Product;
use App\Services\WishlistService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function __construct(private readonly WishlistService $wishlist)
    {
    }

    public function show(Product $product, Request $request): Response
    {
        $product->load(['category', 'variants']);

        $user = $request->user();

        return Inertia::render('Product/Show', [
            'product' => (new ProductDetailResource($product))->resolve(),
            // Per-viewer state, not a property of the product itself — kept as a
            // sibling prop rather than folded into ProductDetailResource.
            'isWishlisted' => $user ? $this->wishlist->isWishlisted($user, $product) : false,
        ]);
    }
}
