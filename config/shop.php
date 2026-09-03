<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Collection / catalogue tuning
    |--------------------------------------------------------------------------
    */

    'per_page' => 12,

    'new_arrival_days' => 30,

    'low_stock_threshold' => 5,

    // Category slug that backs the "Accessories" collection.
    'accessories_category' => 'travel-accessories',

    /*
    | Preset price buckets for the collection filter. Single source of truth —
    | the Form Request validates against these keys and CollectionService maps
    | a key to its cents range. `null` means an open-ended bound.
    */
    'price_buckets' => [
        ['key' => 'under-50', 'label' => 'Under $50', 'min_cents' => null, 'max_cents' => 4999],
        ['key' => '50-100', 'label' => '$50 – $100', 'min_cents' => 5000, 'max_cents' => 9999],
        ['key' => '100-200', 'label' => '$100 – $200', 'min_cents' => 10000, 'max_cents' => 19999],
        ['key' => '200-plus', 'label' => '$200+', 'min_cents' => 20000, 'max_cents' => null],
    ],

    /*
    |--------------------------------------------------------------------------
    | Checkout
    |--------------------------------------------------------------------------
    */
    'checkout' => [
        'currency' => 'usd',
        'flat_shipping_cents' => 1500,
        'free_shipping_threshold_cents' => 15000,
        'tax_rate' => 0.0,
    ],

    /*
    |--------------------------------------------------------------------------
    | Support copy
    |--------------------------------------------------------------------------
    |
    | Shipping and returns policy the chatbot is allowed to quote. Kept here so
    | the same wording drives the assistant and any future policy page.
    */
    'support' => [
        'store_name' => 'Travel Gear Co',
        'shipping' => 'Orders ship within 1–2 business days. Shipping is a flat $15, and free on orders over $150. Delivery usually takes 3–7 business days within the US.',
        'returns' => 'Returns are accepted within 30 days of delivery for unused items in their original packaging. Return shipping is free, and refunds go back to the original payment method within 5 business days of us receiving the item.',
    ],

];
