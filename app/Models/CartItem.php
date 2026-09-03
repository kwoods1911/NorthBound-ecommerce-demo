<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    /** @use HasFactory<\Database\Factories\CartItemFactory> */
    use HasFactory;

    protected $fillable = [
        'cart_id',
        'product_id',
        'product_variant_id',
        'quantity',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Live pricing — the single source of truth for what this line costs.
    | Reused verbatim by CheckoutService when snapshotting order_items.
    |--------------------------------------------------------------------------
    */

    public function unitPrice(): Money
    {
        return $this->variant?->price_override_cents ?? $this->product->effective_price;
    }

    public function unitCost(): Money
    {
        return $this->product->cost_price_cents;
    }

    public function lineTotal(): Money
    {
        return $this->unitPrice()->times($this->quantity);
    }
}
