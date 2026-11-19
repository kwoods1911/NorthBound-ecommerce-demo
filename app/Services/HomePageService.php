<?php

namespace App\Services;

use App\Enums\CollectionType;
use App\Http\Resources\ProductCardResource;
use App\Models\Product;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;

class HomePageService
{
    public function __construct(
        private readonly HeroImageService $heroImages,
        private readonly CollectionService $collections,
    ) {
    }

    /**
     * Every prop the homepage needs, assembled in one place.
     *
     * @return array<string, mixed>
     */
    public function props(): array
    {
        return [
            'hero' => $this->heroImages->activeOrDefault(),
            'collectionTiles' => $this->collectionTiles(),
            'featuredProducts' => $this->featuredProducts(),
            'support' => config('shop.support'),
        ];
    }

    /**
     * Best sellers, capped — real catalogue cards, not placeholders.
     */
    private function featuredProducts(): AnonymousResourceCollection
    {
        $products = Product::query()
            ->bestSellers()
            ->with('variants')
            ->take((int) config('shop.featured_count'))
            ->get();

        return ProductCardResource::collection($products);
    }

    /**
     * One visual entry tile per collection, each with a representative image
     * pulled from that collection's own base scope (reused from CollectionService).
     *
     * @return array<int, array{key: string, title: string, url: string, image: ?string}>
     */
    private function collectionTiles(): array
    {
        return array_map(function (CollectionType $type): array {
            $image = $this->collections->baseQuery($type)->value('image_path');

            return [
                'key' => $type->value,
                'title' => $type->title(),
                'url' => $this->url($type),
                'image' => $image ? Storage::url($image) : null,
            ];
        }, CollectionType::cases());
    }

    private function url(CollectionType $type): string
    {
        return match ($type) {
            CollectionType::NewArrivals => route('collections.new-arrivals'),
            CollectionType::BestSellers => route('collections.best-sellers'),
            CollectionType::Accessories => route('collections.accessories'),
            CollectionType::Deals => route('collections.deals'),
        };
    }
}
