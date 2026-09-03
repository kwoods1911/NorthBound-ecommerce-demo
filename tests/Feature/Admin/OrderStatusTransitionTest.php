<?php

use App\Enums\OrderStatus;
use App\Exceptions\OrderStatusException;
use App\Filament\Resources\OrderResource\Pages\ViewOrder;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderStatusService;

beforeEach(function () {
    $this->service = app(OrderStatusService::class);
});

it('advances status and stamps the matching timestamp', function () {
    $order = Order::factory()->create(['status' => OrderStatus::Paid]);

    $this->service->transition($order, OrderStatus::Shipped);

    expect($order->fresh())
        ->status->toBe(OrderStatus::Shipped)
        ->shipped_at->not->toBeNull();
});

it('rejects a transition that is not allowed from the current status', function () {
    $order = Order::factory()->create(['status' => OrderStatus::Delivered]);

    expect(fn () => $this->service->transition($order, OrderStatus::Pending))
        ->toThrow(OrderStatusException::class);

    expect($order->fresh()->status)->toBe(OrderStatus::Delivered);
});

it('does not touch inventory when an order is marked paid', function () {
    $product = Product::factory()->create(['stock_quantity' => 10, 'sales_count' => 0]);
    $order = Order::factory()->create(['status' => OrderStatus::Pending]);

    $this->service->transition($order, OrderStatus::Paid);

    expect($product->fresh())
        ->stock_quantity->toBe(10)
        ->sales_count->toBe(0);
});

it('lets an admin walk an order through the lifecycle from the view page', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));
    $order = Order::factory()->create(['status' => OrderStatus::Pending]);

    $view = fn () => Livewire\Livewire::test(
        ViewOrder::class,
        ['record' => $order->getRouteKey()],
    );

    $view()->callAction('mark_paid');
    expect($order->fresh()->status)->toBe(OrderStatus::Paid);

    $view()->callAction('mark_shipped');
    expect($order->fresh()->status)->toBe(OrderStatus::Shipped);

    $view()->callAction('mark_delivered');
    expect($order->fresh())
        ->status->toBe(OrderStatus::Delivered)
        ->delivered_at->not->toBeNull();
});

it('only offers the transitions allowed from the current status', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));
    $order = Order::factory()->create(['status' => OrderStatus::Delivered]);

    Livewire\Livewire::test(
        ViewOrder::class,
        ['record' => $order->getRouteKey()],
    )
        ->assertActionDoesNotExist('mark_shipped')
        ->assertActionDoesNotExist('mark_cancelled');
});
