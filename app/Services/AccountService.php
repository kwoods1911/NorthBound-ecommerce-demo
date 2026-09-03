<?php

namespace App\Services;

use App\Http\Resources\OrderResource;
use App\Models\User;

/**
 * Read model for the customer "My Account" hub.
 */
class AccountService
{
    /**
     * @return array<string, mixed>
     */
    public function overview(User $user): array
    {
        $recentOrders = $user->orders()
            ->with('items')
            ->latest()
            ->limit(5)
            ->get();

        return [
            'recentOrders' => OrderResource::collection($recentOrders)->resolve(),
            'orderCount' => $user->orders()->count(),
            'wishlistCount' => $user->wishlistProducts()->count(),
        ];
    }
}
