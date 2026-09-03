<?php

namespace App\Models;

use App\Casts\MoneyCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    /** @use HasFactory<\Database\Factories\ProductVariantFactory> */
    use HasFactory;

    protected $fillable = [
        'product_id',
        'color',
        'size',
        'image_path',
        'price_override_cents',
        'sku',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price_override_cents' => MoneyCast::class,
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** Human-readable "Colour / Size" label, used for order-item snapshots. */
    public function label(): string
    {
        return collect([$this->color, $this->size])->filter()->implode(' / ');
    }
}
