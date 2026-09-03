<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;

it('redirects guests to login', function () {
    $this->get('/account')->assertRedirect('/login');
});

it('shows the signed-in customer their recent orders and counts', function () {
    $user = User::factory()->create();

    $order = Order::factory()->for($user)->create(['status' => OrderStatus::Delivered]);
    OrderItem::factory(2)->for($order)->create();

    $this->actingAs($user)
        ->get('/account')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('account/Index')
            ->where('orderCount', 1)
            ->where('wishlistCount', 0)
            ->has('recentOrders', 1)
            ->where('recentOrders.0.orderNumber', $order->order_number)
        );
});
