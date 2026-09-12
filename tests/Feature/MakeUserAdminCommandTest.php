<?php

use App\Models\User;

test('grants admin access to an existing user', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->artisan('user:make-admin', ['email' => $user->email])
        ->expectsOutputToContain('can now manage the shop')
        ->assertExitCode(0);

    expect($user->fresh()->is_admin)->toBeTrue();
});

test('fails when the requested user does not exist', function () {
    $this->artisan('user:make-admin', ['email' => 'missing@example.com'])
        ->expectsOutputToContain('No user was found')
        ->assertExitCode(1);

});
