<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('guests are redirected to the login page when updating their identity', function () {
    $response = $this->put(route('identity.update'), [
        'firstname' => 'Grace',
        'lastname' => 'Hopper',
        'grade_level' => '1i',
        'status' => 'internal',
    ]);

    $response->assertRedirect(route('login'));

    $this->assertGuest();
});

test('an authenticated user can save their identity data', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->put(route('identity.update'), [
            'firstname' => 'Grace',
            'lastname' => 'Hopper',
            'grade_level' => '1i',
            'status' => 'internal',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('identity.update'));

    $user->refresh();

    expect($user->firstname)->toBe('Grace');
    expect($user->lastname)->toBe('Hopper');
    expect($user->grade_level)->toBe('1i');
    expect($user->is_internal)->toBeTrue();
    expect($user->is_external)->toBeFalse();
});

test('every provisional grade code is accepted', function (string $gradeLevel) {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->put(route('identity.update'), [
            'firstname' => 'Grace',
            'lastname' => 'Hopper',
            'grade_level' => $gradeLevel,
            'status' => 'internal',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('identity.update'));

    expect($user->refresh()->grade_level)->toBe($gradeLevel);
})->with([
    '1i' => ['1i'],
    '2i' => ['2i'],
    '1A' => ['1A'],
    '2A' => ['2A'],
    '1J' => ['1J'],
    '2J' => ['2J'],
]);

test('each identity field is required', function (string $missingField) {
    $user = User::factory()->create([
        'firstname' => 'Grace',
        'lastname' => 'Hopper',
        'grade_level' => '1i',
    ]);

    $payload = [
        'firstname' => 'Ada',
        'lastname' => 'Lovelace',
        'grade_level' => '2i',
        'status' => 'internal',
    ];
    unset($payload[$missingField]);

    $response = $this
        ->actingAs($user)
        ->put(route('identity.update'), $payload);

    $response->assertSessionHasErrors($missingField);

    $user->refresh();

    expect($user->firstname)->toBe('Grace');
    expect($user->lastname)->toBe('Hopper');
    expect($user->grade_level)->toBe('1i');
})->with([
    'firstname' => ['firstname'],
    'lastname' => ['lastname'],
    'grade_level' => ['grade_level'],
    'status' => ['status'],
]);

test('an invalid grade level is rejected and nothing is persisted', function (string $gradeLevel) {
    $user = User::factory()->create([
        'firstname' => 'Grace',
        'lastname' => 'Hopper',
        'grade_level' => '1i',
    ]);

    $response = $this
        ->actingAs($user)
        ->put(route('identity.update'), [
            'firstname' => 'Ada',
            'lastname' => 'Lovelace',
            'grade_level' => $gradeLevel,
            'status' => 'internal',
        ]);

    $response->assertSessionHasErrors('grade_level');

    $user->refresh();

    expect($user->firstname)->toBe('Grace');
    expect($user->lastname)->toBe('Hopper');
    expect($user->grade_level)->toBe('1i');
})->with([
    'unknown label' => ['10th grade'],
    'not a school code' => ['banana'],
    'nonexistent grade' => ['3i'],
    'empty string' => [''],
]);

test('status must be exactly internal or external', function (string $status) {
    $user = User::factory()->create([
        'firstname' => 'Grace',
        'lastname' => 'Hopper',
        'grade_level' => '1i',
        'is_internal' => true,
        'is_external' => false,
    ]);

    $response = $this
        ->actingAs($user)
        ->put(route('identity.update'), [
            'firstname' => 'Ada',
            'lastname' => 'Lovelace',
            'grade_level' => '2i',
            'status' => $status,
        ]);

    $response->assertSessionHasErrors('status');

    $user->refresh();

    expect($user->firstname)->toBe('Grace');
    expect($user->lastname)->toBe('Hopper');
    expect($user->grade_level)->toBe('1i');
    expect($user->is_internal)->toBe(true);
    expect($user->is_external)->toBe(false);
})->with([
    'unknown status' => ['banana'],
    'both statuses' => ['internal,external'],
    'empty string' => [''],
]);

test('firstname and lastname longer than 255 characters are rejected', function (string $field) {
    $user = User::factory()->create([
        'firstname' => 'Grace',
        'lastname' => 'Hopper',
    ]);

    $payload = [
        'firstname' => 'Ada',
        'lastname' => 'Lovelace',
        'grade_level' => '1i',
        'status' => 'internal',
    ];
    $payload[$field] = str_repeat('a', 256);

    $response = $this
        ->actingAs($user)
        ->put(route('identity.update'), $payload);

    $response->assertSessionHasErrors($field);

    $user->refresh();

    expect($user->firstname)->toBe('Grace');
    expect($user->lastname)->toBe('Hopper');
})->with([
    'firstname' => ['firstname'],
    'lastname' => ['lastname'],
]);

test('status internal and external map onto the membership flags', function (string $status, bool $isInternal, bool $isExternal) {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->put(route('identity.update'), [
            'firstname' => 'Grace',
            'lastname' => 'Hopper',
            'grade_level' => '1i',
            'status' => $status,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('identity.update'));

    $user->refresh();

    expect($user->is_internal)->toBe($isInternal);
    expect($user->is_external)->toBe($isExternal);
})->with([
    'internal' => ['internal', true, false],
    'external' => ['external', false, true],
]);

test('resubmitting with the other status flips both flags', function () {
    $user = User::factory()->create();

    $this
        ->actingAs($user)
        ->put(route('identity.update'), [
            'firstname' => 'Grace',
            'lastname' => 'Hopper',
            'grade_level' => '1i',
            'status' => 'internal',
        ])
        ->assertSessionHasNoErrors();

    expect($user->refresh()->is_internal)->toBeTrue();
    expect($user->refresh()->is_external)->toBeFalse();

    $this
        ->actingAs($user)
        ->put(route('identity.update'), [
            'firstname' => 'Grace',
            'lastname' => 'Hopper',
            'grade_level' => '1i',
            'status' => 'external',
        ])
        ->assertSessionHasNoErrors();

    $user->refresh();

    expect($user->is_internal)->toBeFalse();
    expect($user->is_external)->toBeTrue();
});

test('identity data and membership flags are stored encrypted while the model returns plain values', function () {
    $user = User::factory()->create();

    $this
        ->actingAs($user)
        ->put(route('identity.update'), [
            'firstname' => 'Grace',
            'lastname' => 'Hopper',
            'grade_level' => '1i',
            'status' => 'internal',
        ])
        ->assertSessionHasNoErrors();

    $raw = DB::table('users')->where('id', $user->id)->sole();

    expect($raw->firstname)->not->toBe('Grace')
        ->not->toContain('Grace');
    expect($raw->lastname)->not->toBe('Hopper')
        ->not->toContain('Hopper');
    expect($raw->grade_level)->not->toBe('1i')
        ->not->toContain('1i');

    foreach (['is_internal', 'is_external'] as $column) {
        expect($raw->{$column})->not->toBeIn([true, false, 1, 0, '1', '0', 'true', 'false', 't', 'f', 'on', 'off', 'yes', 'no', '']);
    }

    $user->refresh();

    expect($user->firstname)->toBe('Grace');
    expect($user->lastname)->toBe('Hopper');
    expect($user->grade_level)->toBe('1i');
    expect($user->is_internal)->toBe(true);
    expect($user->is_external)->toBe(false);
});
