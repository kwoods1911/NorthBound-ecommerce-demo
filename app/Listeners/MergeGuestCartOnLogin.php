<?php

namespace App\Listeners;

use App\Services\CartService;
use Illuminate\Auth\Events\Login;

class MergeGuestCartOnLogin
{
    public function __construct(private readonly CartService $carts)
    {
    }

    public function handle(Login $event): void
    {
        $this->carts->mergeGuestCartIntoUser($event->user);
    }
}
