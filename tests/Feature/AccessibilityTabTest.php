<?php

use App\Models\User;

test('guests are redirected to login when accessing the accessibility settings page', function () {
    $response = $this->get('/settings/accessibility');

    $response->assertRedirect(route('login'));
});

test('users with unverified email can access the accessibility settings page', function () {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)->get('/settings/accessibility');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('settings/Accessibility'));
});

test('users with verified email can access the accessibility settings page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/settings/accessibility');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('settings/Accessibility'));
});
