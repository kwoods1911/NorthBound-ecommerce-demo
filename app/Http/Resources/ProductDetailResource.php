<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin Product
 */
class ProductDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $threshold = (int) config('shop.low_stock_threshold');

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'category' => [
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ],
            'price' => $this->effective_price->toArray(),
            'compareAtPrice' => $this->on_sale ? $this->price_cents->toArray() : null,
            'gallery' => $this->gallery(),
            'variants' => $this->variants->map(fn ($variant) => [
                'id' => $variant->id,
                'color' => $variant->color,
                'size' => $variant->size,
                'label' => $variant->label(),
                'image' => $variant->image_path ? Storage::url($variant->image_path) : null,
                'price' => ($variant->price_override_cents ?? $this->effective_price)->toArray(),
            ])->all(),
            'inStock' => $this->stock_quantity > 0,
            'lowStock' => $this->stock_quantity > 0 && $this->stock_quantity <= $threshold,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function gallery(): array
    {
        return collect([$this->image_path])
            ->merge($this->variants->pluck('image_path'))
            ->filter()
            ->unique()
            ->map(fn (string $path) => Storage::url($path))
            ->values()
            ->all();
    }
}
