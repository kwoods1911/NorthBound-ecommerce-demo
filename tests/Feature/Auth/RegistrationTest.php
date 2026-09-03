<?php

use App\Models\User;

it('renders the registration screen', function () {
    $this->get('/register')->assertOk();
});

it('registers a new user, signs them in, and lands on the account page', function () {
    $response = $this->post('/register', [
        'name' => 'Casey Rivera',
        'email' => 'casey@example.test',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('account', absolute: false));

    $user = User::firstWhere('email', 'casey@example.test');
    expect($user)->not->toBeNull()
        ->and($user->is_admin)->toBeFalse();
});

it('rejects a duplicate email', function () {
    User::factory()->create(['email' => 'taken@example.test']);

    $this->post('/register', [
        'name' => 'Someone',
        'email' => 'taken@example.test',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});
