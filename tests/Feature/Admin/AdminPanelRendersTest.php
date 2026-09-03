<?php

use App\Enums\OrderStatus;
use App\Filament\Resources\OrderResource\Pages\ListOrders;
use App\Filament\Resources\OrderResource\Pages\ViewOrder;
use App\Filament\Resources\ProductResource\Pages\ListProducts;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Filament\Widgets\DashboardStatsWidget;
use App\Filament\Widgets\LowStockProductsWidget;
use App\Filament\Widgets\NewUsersWidget;
use App\Filament\Widgets\RecentOrdersWidget;
use App\Filament\Widgets\SalesChartWidget;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));

    $product = Product::factory()->create(['stock_quantity' => 1]);
    ProductVariant::factory(2)->for($product)->create();
    $order = Order::factory()->create(['status' => OrderStatus::Delivered]);
    OrderItem::factory(2)->for($order)->for($product)->create();
    $this->order = $order;
});

it('renders the dashboard', function () {
    $this->get('/admin')->assertOk();
});

it('renders each resource list page', function () {
    Livewire::test(ListProducts::class)->assertOk();
    Livewire::test(ListOrders::class)->assertOk();
    Livewire::test(ListUsers::class)->assertOk();
});

it('renders the order view page with its line items', function () {
    Livewire::test(ViewOrder::class, ['record' => $this->order->getRouteKey()])
        ->assertOk()
        ->assertSee($this->order->order_number);
});

it('renders each dashboard widget', function () {
    Livewire::test(DashboardStatsWidget::class)->assertOk();
    Livewire::test(SalesChartWidget::class)->assertOk();
    Livewire::test(RecentOrdersWidget::class)->assertOk();
    Livewire::test(NewUsersWidget::class)->assertOk();
    Livewire::test(LowStockProductsWidget::class)->assertOk();
});
