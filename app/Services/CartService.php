<?php

namespace App\Services;

use App\Exceptions\CartException;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Owns all cart state. Registered as a request-scoped binding so the cart is
 * resolved once per request (shared by the Inertia middleware and controllers).
 */
class CartService
{
    private const SESSION_TOKEN_KEY = 'cart_token';

    private ?Cart $resolved = null;

    /** The current cart, or null if nothing has been added yet. */
    public function currentCart(): ?Cart
    {
        if ($user = auth()->user()) {
            return Cart::query()->with('items')->firstWhere('user_id', $user->id);
        }

        $token = session(self::SESSION_TOKEN_KEY);

        return $token
            ? Cart::query()->with('items')->firstWhere('session_id', $token)
            : null;
    }

    /** The current cart, created (and keyed to the session for guests) if needed. */
    public function resolveCart(): Cart
    {
        if ($this->resolved) {
            return $this->resolved;
        }

        if ($user = auth()->user()) {
            return $this->resolved = Cart::firstOrCreate(['user_id' => $user->id]);
        }

        $token = session(self::SESSION_TOKEN_KEY);

        if (! $token) {
            $token = Str::random(40);
            session([self::SESSION_TOKEN_KEY => $token]);
        }

        return $this->resolved = Cart::firstOrCreate(['session_id' => $token]);
    }

    public function add(int $productId, ?int $variantId, int $quantity = 1): CartItem
    {
        $quantity = max(1, $quantity);
        $product = Product::findOrFail($productId);

        if ($variantId !== null && $product->variants()->whereKey($variantId)->doesntExist()) {
            throw CartException::invalidVariant();
        }

        $cart = $this->resolveCart();

        $item = $cart->items()
            ->where('product_id', $productId)
            ->where('product_variant_id', $variantId)
            ->first();

        $desired = ($item?->quantity ?? 0) + $quantity;

        if ($desired > $product->stock_quantity) {
            throw CartException::insufficientStock($product->name, $product->stock_quantity);
        }

        if ($item) {
            $item->update(['quantity' => $desired]);

            return $item;
        }

        return $cart->items()->create([
            'product_id' => $productId,
            'product_variant_id' => $variantId,
            'quantity' => $quantity,
        ]);
    }

    public function updateQuantity(CartItem $item, int $quantity): void
    {
        if ($quantity <= 0) {
            $item->delete();

            return;
        }

        $item->update(['quantity' => min($quantity, $item->product->stock_quantity)]);
    }

    public function remove(CartItem $item): void
    {
        $item->delete();
    }

    public function clear(?Cart $cart): void
    {
        $cart?->items()->delete();
    }

    /**
     * Fold the guest cart (keyed by the session token) into the user's cart on
     * login. Quantities are summed on collision and re-clamped to stock.
     */
    public function mergeGuestCartIntoUser(User $user): void
    {
        $token = session(self::SESSION_TOKEN_KEY);
        session()->forget(self::SESSION_TOKEN_KEY);

        if (! $token) {
            return;
        }

        $guestCart = Cart::query()->with('items.product')->firstWhere('session_id', $token);

        if (! $guestCart) {
            return;
        }

        $userCart = Cart::firstOrCreate(['user_id' => $user->id]);

        foreach ($guestCart->items as $guestItem) {
            $stock = $guestItem->product?->stock_quantity ?? 0;

            $existing = $userCart->items()
                ->where('product_id', $guestItem->product_id)
                ->where('product_variant_id', $guestItem->product_variant_id)
                ->first();

            $quantity = min($stock, ($existing?->quantity ?? 0) + $guestItem->quantity);

            if ($quantity <= 0) {
                continue;
            }

            $existing
                ? $existing->update(['quantity' => $quantity])
                : $userCart->items()->create([
                    'product_id' => $guestItem->product_id,
                    'product_variant_id' => $guestItem->product_variant_id,
                    'quantity' => $quantity,
                ]);
        }

        $guestCart->delete();
        $this->resolved = null;
    }

    /**
     * Cart contents with live prices, for Inertia props.
     *
     * @return array{count: int, lines: array<int, array<string, mixed>>, subtotal: array<string, mixed>}
     */
    public function summarize(?Cart $cart): array
    {
        $cart?->loadMissing('items.product', 'items.variant');
        $items = $cart?->items ?? collect();

        $lines = $items->map(fn (CartItem $item) => [
            'id' => $item->id,
            'productId' => $item->product_id,
            'name' => $item->product->name,
            'slug' => $item->product->slug,
            'variantLabel' => $item->variant?->label() ?: null,
            'image' => $this->lineImage($item),
            'quantity' => $item->quantity,
            'maxQuantity' => $item->product->stock_quantity,
            'unitPrice' => $item->unitPrice()->toArray(),
            'lineTotal' => $item->lineTotal()->toArray(),
        ])->values()->all();

        return [
            'count' => (int) $items->sum('quantity'),
            'lines' => $lines,
            'subtotal' => Money::fromCents((int) $items->sum(fn (CartItem $i) => $i->lineTotal()->cents))->toArray(),
        ];
    }

    private function lineImage(CartItem $item): ?string
    {
        $path = $item->variant?->image_path ?? $item->product->image_path;

        return $path ? Storage::url($path) : null;
    }
}
