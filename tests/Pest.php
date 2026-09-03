<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Build a valid Stripe-Signature header for a webhook payload, using the same
 * scheme Stripe's SDK verifies against.
 */
function stripeSignature(string $payload, string $secret): string
{
    $timestamp = time();
    $signature = hash_hmac('sha256', "{$timestamp}.{$payload}", $secret);

    return "t={$timestamp},v1={$signature}";
}

/**
 * @return array<string, string>
 */
function shippingPayload(array $overrides = []): array
{
    return array_merge([
        'email' => 'buyer@example.test',
        'name' => 'Test Buyer',
        'line1' => '1 Demo St',
        'city' => 'Testville',
        'state' => 'CA',
        'postal_code' => '90000',
        'country' => 'US',
    ], $overrides);
}

function addToCart(App\Models\Product $product, int $qty = 1, ?App\Models\ProductVariant $variant = null): void
{
    test()->post('/cart', [
        'product_id' => $product->id,
        'variant_id' => $variant?->id,
        'quantity' => $qty,
    ]);
}
