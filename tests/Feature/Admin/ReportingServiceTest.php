<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\ReportingService;

beforeEach(function () {
    $this->reporting = app(ReportingService::class);
});

/**
 * Attach a line item with explicit snapshot figures.
 */
function lineItem(Order $order, int $unitPrice, int $unitCost, int $qty): OrderItem
{
    return OrderItem::factory()->for($order)->create([
        'unit_price_cents' => $unitPrice,
        'unit_cost_cents' => $unitCost,
        'quantity' => $qty,
        'line_total_cents' => $unitPrice * $qty,
    ]);
}

it('counts revenue and profit only from paid, shipped and delivered orders', function () {
    $paid = Order::factory()->create(['status' => OrderStatus::Paid]);
    lineItem($paid, unitPrice: 10_000, unitCost: 4_000, qty: 2); // rev 20000, cost 8000

    $delivered = Order::factory()->create(['status' => OrderStatus::Delivered]);
    lineItem($delivered, unitPrice: 5_000, unitCost: 2_000, qty: 1); // rev 5000, cost 2000

    // Excluded:
    $pending = Order::factory()->create(['status' => OrderStatus::Pending]);
    lineItem($pending, unitPrice: 99_000, unitCost: 1_000, qty: 5);

    $cancelled = Order::factory()->create(['status' => OrderStatus::Cancelled]);
    lineItem($cancelled, unitPrice: 99_000, unitCost: 1_000, qty: 5);

    expect($this->reporting->totalRevenue()->cents)->toBe(25_000)
        ->and($this->reporting->totalProfit()->cents)->toBe(25_000 - 10_000)
        ->and($this->reporting->totalOrders())->toBe(2);
});

it('does not change historical figures when a product price is edited', function () {
    $product = Product::factory()->create(['price_cents' => 10_000, 'cost_price_cents' => 4_000]);

    $order = Order::factory()->create(['status' => OrderStatus::Paid]);
    OrderItem::factory()->for($order)->for($product)->create([
        'unit_price_cents' => 10_000,
        'unit_cost_cents' => 4_000,
        'quantity' => 1,
        'line_total_cents' => 10_000,
    ]);

    $before = $this->reporting->totalProfit()->cents;

    $product->update(['price_cents' => 50_000, 'cost_price_cents' => 1]);

    expect($this->reporting->totalProfit()->cents)->toBe($before);
});

it('lists products at or below the low-stock threshold', function () {
    config(['shop.low_stock_threshold' => 5]);
    Product::factory()->create(['stock_quantity' => 2]);
    Product::factory()->create(['stock_quantity' => 5]);
    Product::factory()->create(['stock_quantity' => 6]);

    expect($this->reporting->lowStockProducts())->toHaveCount(2);
});

it('returns a zero-filled daily sales series', function () {
    $order = Order::factory()->create([
        'status' => OrderStatus::Paid,
        'created_at' => now()->subDays(3),
    ]);
    lineItem($order, unitPrice: 7_000, unitCost: 1_000, qty: 1);

    $series = $this->reporting->salesOverTime(30);

    expect($series)->toHaveCount(30)
        ->and(array_sum($series))->toBe(7_000)
        ->and($series[now()->subDays(3)->toDateString()])->toBe(7_000)
        ->and($series[now()->toDateString()])->toBe(0);
});

it('sums live inventory across the catalogue', function () {
    Product::factory()->create(['stock_quantity' => 10]);
    Product::factory()->create(['stock_quantity' => 15]);

    expect($this->reporting->unitsInInventory())->toBe(25);
});
