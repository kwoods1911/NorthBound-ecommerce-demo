<?php

namespace App\Services;

use App\Http\Resources\ProductCardResource;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WishlistService
{
    public function isWishlisted(User $user, Product $product): bool
    {
        return $user->wishlistProducts()->whereKey($product->id)->exists();
    }

    /**
     * Idempotent — checks first rather than relying on syncWithoutDetaching, so a
     * double-click never trips the (user_id, product_id) unique index.
     */
    public function add(User $user, Product $product): void
    {
        if (! $this->isWishlisted($user, $product)) {
            $user->wishlistProducts()->attach($product->id, ['created_at' => now()]);
        }
    }

    /** detach() is already a no-op when the product isn't on the list. */
    public function remove(User $user, Product $product): void
    {
        $user->wishlistProducts()->detach($product->id);
    }

    public function forAccount(User $user): AnonymousResourceCollection
    {
        $products = $user->wishlistProducts()->with('variants')->get();

        return ProductCardResource::collection($products);
    }
}
