<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users without admin access are forbidden from the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertForbidden();
});

test('admins can visit the dashboard', function () {
    $user = User::factory()->admin()->create();
    $this->actingAs($user);

    $this->get(route('dashboard'))->assertRedirect(route('admin.dashboard'));
});
