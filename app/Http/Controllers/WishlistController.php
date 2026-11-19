<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddToWishlistRequest;
use App\Models\Product;
use App\Services\WishlistService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WishlistController extends Controller
{
    public function __construct(private readonly WishlistService $wishlist)
    {
    }

    public function index(Request $request): Response
    {
        return Inertia::render('account/Wishlist', [
            'products' => $this->wishlist->forAccount($request->user()),
        ]);
    }

    public function store(AddToWishlistRequest $request): RedirectResponse
    {
        $product = Product::findOrFail($request->integer('product_id'));

        $this->wishlist->add($request->user(), $product);

        return back(303);
    }

    public function destroy(Product $product, Request $request): RedirectResponse
    {
        $this->wishlist->remove($request->user(), $product);

        return back(303);
    }
}
