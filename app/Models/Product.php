<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\ProductSort;
use App\Support\Money;
use Database\Factories\ProductFactory;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /**
     * SQL for the price a customer actually pays — discount price when set,
     * otherwise the base price. The Money cast can't be used inside WHERE /
     * ORDER BY, so every price filter and sort routes through this string.
     */
    public const EFFECTIVE_PRICE_SQL = 'COALESCE(discount_price_cents, price_cents)';

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'image_path',
        'price_cents',
        'cost_price_cents',
        'discount_price_cents',
        'stock_quantity',
        'sales_count',
    ];

    protected function casts(): array
    {
        return [
            'price_cents' => MoneyCast::class,
            'cost_price_cents' => MoneyCast::class,
            'discount_price_cents' => MoneyCast::class,
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order');
    }

    public function wishlistedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'wishlists')->withPivot('created_at');
    }

    /*
    |--------------------------------------------------------------------------
    | Derived pricing
    |--------------------------------------------------------------------------
    */

    public static function effectivePriceExpression(): Expression
    {
        return DB::raw(self::EFFECTIVE_PRICE_SQL);
    }

    protected function effectivePrice(): Attribute
    {
        return Attribute::get(fn () => $this->discount_price_cents ?? $this->price_cents);
    }

    protected function onSale(): Attribute
    {
        return Attribute::get(fn () => $this->discount_price_cents instanceof Money);
    }

    /*
    |--------------------------------------------------------------------------
    | Collection scopes — all catalogue query logic lives here, not in controllers
    |--------------------------------------------------------------------------
    */

    /** @param  Builder<Product>  $query */
    public function scopeNewArrivals(Builder $query): Builder
    {
        return $query->latest();
    }

    /** @param  Builder<Product>  $query */
    public function scopeBestSellers(Builder $query): Builder
    {
        return $query->orderByDesc('sales_count');
    }

    /** @param  Builder<Product>  $query */
    public function scopeInCategory(Builder $query, string $slug): Builder
    {
        return $query->whereHas('category', fn (Builder $c) => $c->where('slug', $slug));
    }

    /**
     * Looser category match for free-text search — exact slug or a partial name.
     *
     * @param  Builder<Product>  $query
     */
    public function scopeInCategoryLike(Builder $query, string $term): Builder
    {
        return $query->whereHas('category', fn (Builder $c) => $c
            ->where('slug', $term)
            ->orWhere('name', 'like', '%'.$term.'%'));
    }

    /**
     * Free-text match against the product name and description.
     *
     * @param  Builder<Product>  $query
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(fn (Builder $q) => $q
            ->where('name', 'like', '%'.$term.'%')
            ->orWhere('description', 'like', '%'.$term.'%'));
    }

    /** @param  Builder<Product>  $query */
    public function scopeMaxPrice(Builder $query, int $cents): Builder
    {
        return $query->whereRaw(self::EFFECTIVE_PRICE_SQL.' <= ?', [$cents]);
    }

    /** @param  Builder<Product>  $query */
    public function scopeDiscounted(Builder $query): Builder
    {
        return $query->whereNotNull('discount_price_cents');
    }

    /**
     * @param  Builder<Product>  $query
     * @param  array<int, string>  $colors
     */
    public function scopeHavingColors(Builder $query, array $colors): Builder
    {
        if ($colors === []) {
            return $query;
        }

        return $query->whereHas('variants', fn (Builder $v) => $v->whereIn('color', $colors));
    }

    /** @param  Builder<Product>  $query */
    public function scopePriceBucket(Builder $query, ?int $minCents, ?int $maxCents): Builder
    {
        return $query
            ->when($minCents !== null, fn (Builder $q) => $q->whereRaw(self::EFFECTIVE_PRICE_SQL.' >= ?', [$minCents]))
            ->when($maxCents !== null, fn (Builder $q) => $q->whereRaw(self::EFFECTIVE_PRICE_SQL.' <= ?', [$maxCents]));
    }

    /** @param  Builder<Product>  $query */
    public function scopeApplySort(Builder $query, ProductSort $sort): Builder
    {
        return match ($sort) {
            ProductSort::PriceLowHigh => $query->orderByRaw(self::EFFECTIVE_PRICE_SQL.' asc'),
            ProductSort::PriceHighLow => $query->orderByRaw(self::EFFECTIVE_PRICE_SQL.' desc'),
            ProductSort::Newest => $query->latest(),
            ProductSort::Featured => $query->orderByDesc('sales_count'),
        };
    }
}
