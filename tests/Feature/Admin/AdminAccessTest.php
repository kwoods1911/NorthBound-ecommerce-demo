<?php

use App\Models\User;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

it('redirects guests to the admin login', function () {
    $this->get('/admin')->assertRedirect('/admin/login');
});

it('denies non-admin users', function () {
    $this->actingAs(User::factory()->create(['is_admin' => false]));

    $this->get('/admin')->assertForbidden();
});

it('allows admin users', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));

    $this->get('/admin')->assertOk();
});

it('gates every admin resource behind is_admin', function () {
    $this->actingAs(User::factory()->create(['is_admin' => false]));

    $this->get('/admin/products')->assertForbidden();
    $this->get('/admin/orders')->assertForbidden();
    $this->get('/admin/users')->assertForbidden();
});

it('exposes no create or edit route for orders', function () {
    expect(fn () => route('filament.admin.resources.orders.create'))
        ->toThrow(RouteNotFoundException::class);
    expect(fn () => route('filament.admin.resources.orders.edit', ['record' => 1]))
        ->toThrow(RouteNotFoundException::class);
});
