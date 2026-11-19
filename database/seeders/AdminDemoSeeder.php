<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\HeroImage;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Realistic catalogue + order history so the admin dashboard is populated
 * during a demo. Idempotent-ish: safe to run on a fresh migrate:fresh.
 */
class AdminDemoSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@travelgear.test',
            'password' => 'password',
            'is_admin' => true,
        ]);

        $knownCustomer = User::factory()->create([
            'name' => 'Casey Rivera',
            'email' => 'customer@travelgear.test',
            'password' => 'password',
        ]);

        $customers = User::factory(11)->create()->push($knownCustomer);

        $categories = collect([
            ['name' => 'Suitcases', 'slug' => 'suitcases'],
            ['name' => 'Carry-ons', 'slug' => 'carry-ons'],
            ['name' => 'Weekender Bags', 'slug' => 'weekender-bags'],
            ['name' => 'Packing Cubes', 'slug' => 'packing-cubes'],
            ['name' => 'Travel Accessories', 'slug' => 'travel-accessories'],
        ])->map(fn (array $c, int $i) => Category::create([
            ...$c,
            'description' => "{$c['name']} built for the way you travel.",
            'sort_order' => $i,
        ]));

        $products = $categories->flatMap(function (Category $category) {
            return Product::factory(5)
                ->for($category)
                ->create()
                ->each(function (Product $product) {
                    ProductVariant::factory(fake()->numberBetween(2, 4))
                        ->for($product)
                        ->create();

                    if (fake()->boolean(30)) {
                        $product->update([
                            'discount_price_cents' => (int) round($product->price_cents->cents * 0.8),
                        ]);
                    }
                });
        });

        // ~50 orders spread across the last 30 days.
        foreach (range(1, 50) as $i) {
            $this->makeOrder($products, $customers, Carbon::today()->subDays(fake()->numberBetween(0, 29)));
        }

        // Guarantee the demo login has order history to show on /account.
        foreach ([2, 9, 21] as $daysAgo) {
            $this->makeOrder($products, collect([$knownCustomer]), Carbon::today()->subDays($daysAgo));
        }

        $this->seedHeroImages();
    }

    /**
     * One live hero plus a prepared-but-inactive alternate, so a demo can show
     * switching the homepage hero from /admin without a deploy.
     */
    private function seedHeroImages(): void
    {
        HeroImage::create([
            'image_path' => 'products/01M1P0CD9GCVE4DCF32VHQ5WMX.png',
            'headline' => 'Gear made for the way you travel.',
            'subheading' => 'Considered luggage, carry-ons and accessories — built to move.',
            'cta_label' => 'Shop new arrivals',
            'cta_url' => '/new-arrivals',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        HeroImage::create([
            'image_path' => null,
            'headline' => 'The trip starts before you pack.',
            'subheading' => 'Best-selling bags, ready when you are.',
            'cta_label' => 'Shop best sellers',
            'cta_url' => '/best-sellers',
            'is_active' => false,
            'sort_order' => 1,
        ]);
    }

    /**
     * @param  Collection<int, Product>  $products
     * @param  Collection<int, User>  $customers
     */
    private function makeOrder(Collection $products, Collection $customers, Carbon $placedAt): void
    {
        $status = fake()->randomElement([
            OrderStatus::Delivered, OrderStatus::Delivered, OrderStatus::Shipped,
            OrderStatus::Paid, OrderStatus::Pending, OrderStatus::Cancelled,
        ]);

        $user = fake()->boolean(70) ? $customers->random() : null;
        $lineProducts = $products->random(fake()->numberBetween(1, 3));

        $factory = Order::factory();
        $factory = $user ? $factory : $factory->guest();

        $order = $factory->create([
            'user_id' => $user?->id,
            'status' => $status,
            'created_at' => $placedAt,
            'updated_at' => $placedAt,
            'subtotal_cents' => 0,
            'shipping_cents' => 0,
            'tax_cents' => 0,
            'total_cents' => 0,
            ...$this->statusTimestamps($status, $placedAt),
        ]);

        $subtotal = 0;

        foreach ($lineProducts as $product) {
            $qty = fake()->numberBetween(1, 2);
            $unitPrice = $product->effective_price->cents;
            $unitCost = $product->cost_price_cents->cents;
            $lineTotal = $unitPrice * $qty;
            $subtotal += $lineTotal;

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'product_variant_id' => null,
                'product_name' => $product->name,
                'variant_label' => null,
                'sku' => 'TGC-'.strtoupper(Str::random(8)),
                'unit_price_cents' => $unitPrice,
                'unit_cost_cents' => $unitCost,
                'quantity' => $qty,
                'line_total_cents' => $lineTotal,
            ]);
        }

        $shipping = $subtotal >= config('shop.checkout.free_shipping_threshold_cents') ? 0 : config('shop.checkout.flat_shipping_cents');

        $order->forceFill([
            'subtotal_cents' => $subtotal,
            'shipping_cents' => $shipping,
            'tax_cents' => 0,
            'total_cents' => $subtotal + $shipping,
        ])->save();
    }

    /**
     * @return array<string, Carbon>
     */
    private function statusTimestamps(OrderStatus $status, Carbon $placedAt): array
    {
        return match ($status) {
            OrderStatus::Pending => [],
            OrderStatus::Cancelled => ['cancelled_at' => $placedAt->copy()->addHours(2)],
            OrderStatus::Paid => ['paid_at' => $placedAt->copy()->addMinutes(5)],
            OrderStatus::Shipped => [
                'paid_at' => $placedAt->copy()->addMinutes(5),
                'shipped_at' => $placedAt->copy()->addDay(),
            ],
            OrderStatus::Delivered => [
                'paid_at' => $placedAt->copy()->addMinutes(5),
                'shipped_at' => $placedAt->copy()->addDay(),
                'delivered_at' => $placedAt->copy()->addDays(4),
            ],
        };
    }
}
