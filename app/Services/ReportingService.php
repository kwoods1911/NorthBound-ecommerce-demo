<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * All aggregate figures for the admin dashboard live here so the same numbers
 * can be unit-tested and reused. Widgets call this service; they never run
 * their own queries.
 *
 * Revenue and profit are computed from `order_items` snapshots only, never by
 * joining back to `products` — editing a product's price or cost must not
 * retroactively change reported figures.
 */
class ReportingService
{
    /**
     * Merchandise revenue (excludes shipping and tax, which are not snapshotted
     * per line) for orders whose payment has cleared.
     */
    public function totalRevenue(): Money
    {
        return Money::fromCents((int) $this->recognisedItems()->sum('line_total_cents'));
    }

    /**
     * Revenue minus snapshotted cost of goods sold, over the same orders.
     */
    public function totalProfit(): Money
    {
        $row = $this->recognisedItems()
            ->selectRaw('COALESCE(SUM(line_total_cents), 0) AS revenue')
            ->selectRaw('COALESCE(SUM(unit_cost_cents * quantity), 0) AS cost')
            ->first();

        return Money::fromCents((int) $row->revenue - (int) $row->cost);
    }

    /**
     * Count of orders whose revenue is recognised.
     */
    public function totalOrders(): int
    {
        return Order::query()->revenueRecognised()->count();
    }

    /**
     * Live sum of on-hand stock across the catalogue.
     */
    public function unitsInInventory(): int
    {
        return (int) Product::query()->sum('stock_quantity');
    }

    /**
     * @return Collection<int, Order>
     */
    public function recentOrders(int $limit = 10): Collection
    {
        return Order::query()->latest()->limit($limit)->get();
    }

    /**
     * @return Collection<int, User>
     */
    public function newUsers(int $limit = 10): Collection
    {
        return User::query()->latest()->limit($limit)->get();
    }

    /**
     * Products at or below the configured low-stock threshold.
     *
     * @return Collection<int, Product>
     */
    public function lowStockProducts(): Collection
    {
        return Product::query()
            ->where('stock_quantity', '<=', $this->lowStockThreshold())
            ->orderBy('stock_quantity')
            ->get();
    }

    public function lowStockThreshold(): int
    {
        return (int) config('shop.low_stock_threshold', 5);
    }

    /**
     * Daily merchandise revenue for the last $days days, oldest first, with
     * zero-filled gaps so the chart has a point for every day.
     *
     * @return array<string, int> ISO date => revenue in cents
     */
    public function salesOverTime(int $days = 30): array
    {
        $start = Carbon::today()->subDays($days - 1);

        $recognised = array_map(fn ($s) => $s->value, OrderStatus::revenueRecognised());

        $byDate = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereIn('orders.status', $recognised)
            ->where('orders.created_at', '>=', $start)
            ->selectRaw('DATE(orders.created_at) AS day')
            ->selectRaw('COALESCE(SUM(order_items.line_total_cents), 0) AS revenue')
            ->groupBy('day')
            ->pluck('revenue', 'day');

        $series = [];

        for ($date = $start->copy(); $date->lte(Carbon::today()); $date->addDay()) {
            $key = $date->toDateString();
            $series[$key] = (int) ($byDate[$key] ?? 0);
        }

        return $series;
    }

    /**
     * OrderItem query scoped to revenue-recognised orders.
     *
     * @return Builder<OrderItem>
     */
    private function recognisedItems()
    {
        return OrderItem::query()
            ->whereHas('order', fn ($q) => $q->revenueRecognised());
    }
}
