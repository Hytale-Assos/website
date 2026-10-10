<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('data page is displayed with the personal data inventory sections', function () {
    $user = User::factory()->create([
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
    ]);

    $this->actingAs($user)
        ->get(route('data.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/Data')
            ->has('sections.account')
            ->has('sections.identification')
            ->has('sections.linked_accounts')
            ->has('sections.security')
            ->where('sections.account.email', 'ada@example.com'),
        );
});

test('guests are redirected to the login page', function () {
    $this->get(route('data.edit'))
        ->assertRedirect(route('login'));
});

test('each visitor only sees their own personal data', function () {
    $internal = User::factory()->create([
        'email' => 'internal@example.com',
        'is_internal' => true,
        'is_external' => false,
    ]);

    $external = User::factory()->create([
        'email' => 'external@example.com',
        'is_internal' => false,
        'is_external' => true,
    ]);

    $this->actingAs($internal)
        ->get(route('data.edit'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('sections.account.email', 'internal@example.com')
            ->where('sections.identification.status', 'internal'));

    $this->actingAs($external)
        ->get(route('data.edit'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('sections.account.email', 'external@example.com')
            ->where('sections.identification.status', 'external'));
});
