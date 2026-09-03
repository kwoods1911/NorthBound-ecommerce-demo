<?php

namespace App\Http\Controllers;

use App\Exceptions\CartException;
use App\Http\Requests\AddToCartRequest;
use App\Http\Requests\UpdateCartItemRequest;
use App\Models\CartItem;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CartController extends Controller
{
    public function __construct(private readonly CartService $carts)
    {
    }

    public function show(): Response
    {
        return Inertia::render('Cart', [
            'cart' => $this->carts->summarize($this->carts->currentCart()),
        ]);
    }

    public function store(AddToCartRequest $request): RedirectResponse
    {
        try {
            $this->carts->add(
                (int) $request->integer('product_id'),
                $request->filled('variant_id') ? (int) $request->integer('variant_id') : null,
                $request->quantity(),
            );
        } catch (CartException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back(303)->with('success', 'Added to your cart.');
    }

    public function update(UpdateCartItemRequest $request, CartItem $item): RedirectResponse
    {
        $this->authorizeItem($item);

        $this->carts->updateQuantity($item, (int) $request->integer('quantity'));

        return back(303);
    }

    public function destroy(CartItem $item): RedirectResponse
    {
        $this->authorizeItem($item);

        $this->carts->remove($item);

        return back(303);
    }

    private function authorizeItem(CartItem $item): void
    {
        abort_unless($item->cart_id === $this->carts->resolveCart()->id, 403);
    }
}
