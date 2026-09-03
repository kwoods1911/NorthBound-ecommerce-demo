<?php

namespace App\Enums;

enum CollectionType: string
{
    case NewArrivals = 'new-arrivals';
    case BestSellers = 'best-sellers';
    case Accessories = 'accessories';
    case Deals = 'deals';

    public function title(): string
    {
        return match ($this) {
            self::NewArrivals => 'New Arrivals',
            self::BestSellers => 'Best Sellers',
            self::Accessories => 'Travel Accessories',
            self::Deals => 'Deals & Bundles',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::NewArrivals => 'The latest additions to the collection — fresh gear for your next trip.',
            self::BestSellers => 'The pieces our travellers reach for again and again.',
            self::Accessories => 'Packing cubes, pouches, tags and the small things that make travel easier.',
            self::Deals => 'Current markdowns and bundle savings. When they are gone, they are gone.',
        };
    }

    public function defaultSort(): ProductSort
    {
        return match ($this) {
            self::NewArrivals => ProductSort::Newest,
            self::BestSellers => ProductSort::Featured,
            self::Accessories => ProductSort::Featured,
            self::Deals => ProductSort::Featured,
        };
    }

    public function showsCategoryFilter(): bool
    {
        return $this !== self::Accessories;
    }

    public function showsOnSaleFilter(): bool
    {
        return $this !== self::Deals;
    }

    /** Category slug this collection is pinned to, if any. */
    public function categorySlug(): ?string
    {
        return $this === self::Accessories
            ? config('shop.accessories_category')
            : null;
    }
}
