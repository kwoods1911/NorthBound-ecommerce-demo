<?php

namespace App\Http\Resources;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Order
 */
class OrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'orderNumber' => $this->order_number,
            'status' => $this->status->value,
            'statusLabel' => $this->status->label(),
            'email' => $this->email,
            'placedAt' => $this->created_at?->toDayDateTimeString(),
            'paidAt' => $this->paid_at?->toDayDateTimeString(),
            'shipping' => [
                'name' => $this->shipping_name,
                'line1' => $this->shipping_line1,
                'line2' => $this->shipping_line2,
                'city' => $this->shipping_city,
                'state' => $this->shipping_state,
                'postalCode' => $this->shipping_postal_code,
                'country' => $this->shipping_country,
            ],
            'totals' => [
                'subtotal' => $this->subtotal_cents->toArray(),
                'shipping' => $this->shipping_cents->toArray(),
                'tax' => $this->tax_cents->toArray(),
                'total' => $this->total_cents->toArray(),
            ],
            'items' => $this->items->map(fn ($item) => [
                'name' => $item->product_name,
                'variantLabel' => $item->variant_label,
                'quantity' => $item->quantity,
                'unitPrice' => $item->unit_price_cents->toArray(),
                'lineTotal' => $item->line_total_cents->toArray(),
            ])->all(),
        ];
    }
}
