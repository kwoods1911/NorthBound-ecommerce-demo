<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\OrderStatus;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'cart_id',
        'order_number',
        'status',
        'email',
        'shipping_name',
        'shipping_line1',
        'shipping_line2',
        'shipping_city',
        'shipping_state',
        'shipping_postal_code',
        'shipping_country',
        'subtotal_cents',
        'shipping_cents',
        'tax_cents',
        'total_cents',
        'stripe_payment_intent_id',
        'paid_at',
        'shipped_at',
        'delivered_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'subtotal_cents' => MoneyCast::class,
            'shipping_cents' => MoneyCast::class,
            'tax_cents' => MoneyCast::class,
            'total_cents' => MoneyCast::class,
            'paid_at' => 'datetime',
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'order_number';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Orders whose revenue is recognised for reporting — payment has cleared.
     * Pending (never paid) and cancelled orders are excluded.
     *
     * @param  Builder<Order>  $query
     */
    public function scopeRevenueRecognised(Builder $query): Builder
    {
        return $query->whereIn('status', OrderStatus::revenueRecognised());
    }

    public function isGuest(): bool
    {
        return $this->user_id === null;
    }

    public function markPaid(string $paymentIntentId): void
    {
        $this->forceFill([
            'status' => OrderStatus::Paid,
            'stripe_payment_intent_id' => $paymentIntentId,
            'paid_at' => now(),
        ])->save();
    }
}
