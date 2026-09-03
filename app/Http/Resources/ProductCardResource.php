<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin Product
 */
class ProductCardResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'url' => '/products/'.$this->slug,
            'price' => $this->effective_price->toArray(),
            'compareAtPrice' => $this->on_sale ? $this->price_cents->toArray() : null,
            'image' => $this->cardImage(),
            'colors' => $this->whenLoaded('variants', fn () => $this->variants
                ->pluck('color')
                ->filter()
                ->unique()
                ->values()
            ),
            'badges' => $this->badges(),
        ];
    }

    private function cardImage(): ?string
    {
        $path = $this->image_path
            ?? $this->whenLoaded('variants', fn () => $this->variants->first()?->image_path, null);

        return $path ? Storage::url($path) : null;
    }

    /**
     * @return array<int, string>
     */
    private function badges(): array
    {
        $badges = [];

        if ($this->created_at?->gt(now()->subDays((int) config('shop.new_arrival_days')))) {
            $badges[] = 'New';
        }

        if ($this->on_sale) {
            $badges[] = 'Sale';
        }

        $threshold = (int) config('shop.low_stock_threshold');

        if ($this->stock_quantity === 0) {
            $badges[] = 'Sold out';
        } elseif ($this->stock_quantity <= $threshold) {
            $badges[] = 'Low stock';
        }

        return $badges;
    }
}
