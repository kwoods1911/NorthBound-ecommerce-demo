<?php

use App\Enums\OrderStatus;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Facades\Schema;

it('has all domain tables', function () {
    foreach ([
        'categories', 'products', 'product_variants',
        'carts', 'cart_items', 'wishlists',
        'orders', 'order_items',
        'chat_conversations', 'chat_messages',
    ] as $table) {
        expect(Schema::hasTable($table))->toBeTrue("missing table [$table]");
    }
});

it('snapshots price and cost on order_items', function () {
    expect(Schema::hasColumns('order_items', [
        'product_name', 'variant_label', 'sku',
        'unit_price_cents', 'unit_cost_cents', 'quantity', 'line_total_cents',
    ]))->toBeTrue();
});

it('flags admin users', function () {
    expect(Schema::hasColumn('users', 'is_admin'))->toBeTrue();

    $admin = User::factory()->create(['is_admin' => true]);

    expect($admin->is_admin)->toBeTrue();
});

it('wires product, variant and category relationships', function () {
    $category = Category::create([
        'name' => 'Carry-ons',
        'slug' => 'carry-ons',
    ]);

    $product = $category->products()->create([
        'name' => 'The Carry-On',
        'slug' => 'the-carry-on',
        'description' => 'A carry-on suitcase.',
        'price_cents' => 22500,
        'cost_price_cents' => 9000,
    ]);

    $variant = $product->variants()->create([
        'color' => 'Navy',
        'size' => 'Carry-On',
        'image_path' => 'variants/navy.jpg',
    ]);

    expect($product->category->is($category))->toBeTrue()
        ->and($product->variants->first()->is($variant))->toBeTrue()
        ->and($product->price_cents)->toBeInstanceOf(Money::class)
        ->and($product->price_cents->cents)->toBe(22500)
        ->and($product->price_cents->format())->toBe('$225.00')
        ->and($variant->label())->toBe('Navy / Carry-On');
});

it('supports a guest cart with items', function () {
    $product = Product::factory()->create();

    $cart = Cart::create(['session_id' => 'guest-session-abc']);
    $item = $cart->items()->create([
        'product_id' => $product->id,
        'quantity' => 2,
    ]);

    expect($cart->user_id)->toBeNull()
        ->and($cart->items)->toHaveCount(1)
        ->and($item->product->is($product))->toBeTrue();
});

it('lets a user wishlist products', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create();

    $user->wishlistProducts()->attach($product, ['created_at' => now()]);

    expect($user->wishlistProducts)->toHaveCount(1)
        ->and($product->wishlistedBy)->toHaveCount(1);
});

it('records an order with snapshotted line items and an enum status', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create();

    $order = Order::create([
        'user_id' => $user->id,
        'order_number' => 'TGC-20260829-0001',
        'email' => $user->email,
        'shipping_name' => 'Test Buyer',
        'shipping_line1' => '1 Test St',
        'shipping_city' => 'Testville',
        'shipping_postal_code' => '00000',
        'shipping_country' => 'US',
        'subtotal_cents' => 20000,
        'total_cents' => 20000,
    ]);

    $order->items()->create([
        'product_id' => $product->id,
        'product_name' => $product->name,
        'unit_price_cents' => 10000,
        'unit_cost_cents' => 4000,
        'quantity' => 2,
        'line_total_cents' => 20000,
    ]);

    $order->refresh();

    expect($order->status)->toBe(OrderStatus::Pending)
        ->and($order->total_cents->format())->toBe('$200.00')
        ->and($order->items->first()->unit_cost_cents->cents)->toBe(4000)
        ->and($order->user->is($user))->toBeTrue();
});

it('keeps order line items after the product is deleted', function () {
    $product = Product::factory()->create();
    $order = Order::factory()->create();

    $item = $order->items()->create([
        'product_id' => $product->id,
        'product_name' => $product->name,
        'unit_price_cents' => 5000,
        'unit_cost_cents' => 2000,
        'quantity' => 1,
        'line_total_cents' => 5000,
    ]);

    $product->delete();

    expect($item->fresh()->product_id)->toBeNull()
        ->and($item->fresh()->product_name)->toBe($product->name);
});

it('stores session-scoped chat history with tool payloads', function () {
    $conversation = ChatConversation::create(['session_id' => 'chat-session-xyz']);

    $conversation->messages()->create(['role' => 'user', 'content' => 'Any carry-ons under $250?']);
    $toolMessage = $conversation->messages()->create([
        'role' => 'tool',
        'tool_name' => 'searchProducts',
        'tool_payload' => ['query' => 'carry-on', 'product_ids' => [1, 2, 3]],
    ]);

    expect($conversation->messages)->toHaveCount(2)
        ->and($toolMessage->fresh()->tool_payload['product_ids'])->toBe([1, 2, 3]);
});
