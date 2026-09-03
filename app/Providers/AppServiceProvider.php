<?php

namespace App\Providers;

use App\Listeners\MergeGuestCartOnLogin;
use App\Services\CartService;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // One cart per request, shared by the Inertia middleware and controllers.
        $this->app->scoped(CartService::class);
    }

    public function boot(): void
    {
        Event::listen(Login::class, MergeGuestCartOnLogin::class);
    }
}
