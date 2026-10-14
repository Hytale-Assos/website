<?php

use App\Models\User;
use Illuminate\Support\Facades\Log;

test('member hytale id is stored in its canonical lowercase trimmed form', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->put(route('accounts.update'), [
        'hytale_id' => '  9F8B7C6D-5E4F-4a3b-2c1d-0E9F8A7B6C5D  ',
    ]);

    $response->assertSessionHasNoErrors();
    expect($user->fresh()->hytale_id)->toBe('9f8b7c6d-5e4f-4a3b-2c1d-0e9f8a7b6c5d');
});

test('rejects a hytale id already claimed by another member in a different case', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();

    $this->actingAs($owner)->put(route('accounts.update'), [
        'hytale_id' => '9f8b7c6d-5e4f-4a3b-2c1d-0e9f8a7b6c5d',
    ])->assertSessionHasNoErrors();

    $this->actingAs($member)->put(route('accounts.update'), [
        'hytale_id' => '123e4567-e89b-42d3-a456-426614174000',
    ])->assertSessionHasNoErrors();

    $response = $this->actingAs($member)->put(route('accounts.update'), [
        'hytale_id' => ' 9F8B7C6D-5E4F-4a3b-2c1d-0E9F8A7B6C5D ',
    ]);

    $response->assertSessionHasErrors('hytale_id');
    expect($member->fresh()->hytale_id)->toBe('123e4567-e89b-42d3-a456-426614174000');
});

test('logs the previous and the new hytale id when a member changes it', function () {
    $user = User::factory()->create();
    $log = Log::spy();

    $this->actingAs($user)->put(route('accounts.update'), [
        'hytale_id' => '9f8b7c6d-5e4f-4a3b-2c1d-0e9f8a7b6c5d',
    ])->assertSessionHasNoErrors();

    $this->actingAs($user)->put(route('accounts.update'), [
        'hytale_id' => '123e4567-e89b-42d3-a456-426614174000',
    ])->assertSessionHasNoErrors();

    $log->shouldHaveReceived('info', function ($message, $context = []) use ($user) {
        return str_contains($message, 'Hytale identity changed.')
            && in_array($user->id, $context)
            && in_array('9f8b7c6d-5e4f-4a3b-2c1d-0e9f8a7b6c5d', $context)
            && in_array('123e4567-e89b-42d3-a456-426614174000', $context);
    });
});

test('does not log an identity change when a member changes only their game nickname', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->put(route('accounts.update'), [
        'hytale_id' => '9f8b7c6d-5e4f-4a3b-2c1d-0e9f8a7b6c5d',
        'hytale_nickname' => 'OldNickname',
    ])->assertSessionHasNoErrors();

    $log = Log::spy();

    $response = $this->actingAs($user)->put(route('accounts.update'), [
        'hytale_id' => '9f8b7c6d-5e4f-4a3b-2c1d-0e9f8a7b6c5d',
        'hytale_nickname' => 'NewNickname',
    ]);

    $response->assertSessionHasNoErrors();
    expect($user->fresh()->hytale_nickname)->toBe('NewNickname');

    $log->shouldNotHaveReceived('info', function ($message, $context = []) {
        return str_contains($message, 'Hytale identity changed.');
    });
});
