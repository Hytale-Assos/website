<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('identity page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('identity.edit'));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('settings/Identity'));
});

test('guests are redirected to the login page when visiting the identity page', function () {
    $response = $this->get(route('identity.edit'));

    $response->assertRedirect(route('login'));

    $this->assertGuest();
});

test('guests are redirected to the login page when updating their identity', function () {
    $response = $this->put(route('identity.update'), [
        'firstname' => 'Grace',
        'lastname' => 'Hopper',
        'grade_level' => '1i',
        'school_email' => 'grace@ecole.fr',
    ]);

    $response->assertRedirect(route('login'));

    $this->assertGuest();
});

test('an internal member can save their identity data', function () {
    config(['members.school_email_domains' => ['ecole.fr']]);
    $user = User::factory()->create(['is_internal' => true]);

    $response = $this
        ->actingAs($user)
        ->put(route('identity.update'), [
            'firstname' => 'Grace',
            'lastname' => 'Hopper',
            'grade_level' => '1i',
            'school_email' => 'grace@ecole.fr',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('identity.update'));

    $user->refresh();

    expect($user->firstname)->toBe('Grace');
    expect($user->lastname)->toBe('Hopper');
    expect($user->grade_level)->toBe('1i');
    expect($user->school_email)->toBe('grace@ecole.fr');
});

test('every provisional grade code is accepted', function (string $gradeLevel) {
    config(['members.school_email_domains' => ['ecole.fr']]);
    $user = User::factory()->create(['is_internal' => true]);

    $response = $this
        ->actingAs($user)
        ->put(route('identity.update'), [
            'firstname' => 'Grace',
            'lastname' => 'Hopper',
            'grade_level' => $gradeLevel,
            'school_email' => 'grace@ecole.fr',
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

test('each identity field is required for internal members', function (string $missingField) {
    config(['members.school_email_domains' => ['ecole.fr']]);
    $user = User::factory()->create([
        'is_internal' => true,
        'firstname' => 'Grace',
        'lastname' => 'Hopper',
        'grade_level' => '1i',
        'school_email' => 'grace@ecole.fr',
    ]);

    $payload = [
        'firstname' => 'Ada',
        'lastname' => 'Lovelace',
        'grade_level' => '2i',
        'school_email' => 'ada@ecole.fr',
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
    expect($user->school_email)->toBe('grace@ecole.fr');
})->with([
    'firstname' => ['firstname'],
    'lastname' => ['lastname'],
    'grade_level' => ['grade_level'],
    'school_email' => ['school_email'],
]);

test('an invalid grade level is rejected and nothing is persisted', function (string $gradeLevel) {
    config(['members.school_email_domains' => ['ecole.fr']]);
    $user = User::factory()->create([
        'is_internal' => true,
        'firstname' => 'Grace',
        'lastname' => 'Hopper',
        'grade_level' => '1i',
        'school_email' => 'grace@ecole.fr',
    ]);

    $response = $this
        ->actingAs($user)
        ->put(route('identity.update'), [
            'firstname' => 'Ada',
            'lastname' => 'Lovelace',
            'grade_level' => $gradeLevel,
            'school_email' => 'ada@ecole.fr',
        ]);

    $response->assertSessionHasErrors('grade_level');

    $user->refresh();

    expect($user->firstname)->toBe('Grace');
    expect($user->lastname)->toBe('Hopper');
    expect($user->grade_level)->toBe('1i');
    expect($user->school_email)->toBe('grace@ecole.fr');
})->with([
    'unknown label' => ['10th grade'],
    'not a school code' => ['banana'],
    'nonexistent grade' => ['3i'],
    'empty string' => [''],
]);

test('school email must be a valid address on a configured school domain', function (string $schoolEmail) {
    config(['members.school_email_domains' => ['ecole.fr']]);
    $user = User::factory()->create([
        'is_internal' => true,
        'firstname' => 'Grace',
        'lastname' => 'Hopper',
        'grade_level' => '1i',
    ]);

    $response = $this
        ->actingAs($user)
        ->put(route('identity.update'), [
            'firstname' => 'Ada',
            'lastname' => 'Lovelace',
            'grade_level' => '2i',
            'school_email' => $schoolEmail,
        ]);

    $response->assertSessionHasErrors('school_email');

    $user->refresh();

    expect($user->firstname)->toBe('Grace');
    expect($user->lastname)->toBe('Hopper');
    expect($user->grade_level)->toBe('1i');
    expect($user->school_email)->toBeNull();
})->with([
    'not an email address' => ['banana'],
    'unknown domain' => ['grace@mail.com'],
    'different school domain' => ['grace@ecole.com'],
]);

test('school email already used by another member is rejected', function () {
    config(['members.school_email_domains' => ['ecole.fr']]);
    User::factory()->create(['is_internal' => true, 'school_email' => 'taken@ecole.fr']);
    $user = User::factory()->create([
        'is_internal' => true,
        'firstname' => 'Grace',
        'lastname' => 'Hopper',
        'grade_level' => '1i',
    ]);

    $response = $this
        ->actingAs($user)
        ->put(route('identity.update'), [
            'firstname' => 'Ada',
            'lastname' => 'Lovelace',
            'grade_level' => '2i',
            'school_email' => 'taken@ecole.fr',
        ]);

    $response->assertSessionHasErrors('school_email');

    $user->refresh();

    expect($user->school_email)->toBeNull();
    expect($user->firstname)->toBe('Grace');
});

test('a member can resubmit their own school email unchanged', function () {
    config(['members.school_email_domains' => ['ecole.fr']]);
    $user = User::factory()->create([
        'is_internal' => true,
        'firstname' => 'Grace',
        'lastname' => 'Hopper',
        'grade_level' => '1i',
        'school_email' => 'grace@ecole.fr',
    ]);

    $response = $this
        ->actingAs($user)
        ->put(route('identity.update'), [
            'firstname' => 'Ada',
            'lastname' => 'Lovelace',
            'grade_level' => '2A',
            'school_email' => 'grace@ecole.fr',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('identity.update'));

    $user->refresh();

    expect($user->school_email)->toBe('grace@ecole.fr');
    expect($user->firstname)->toBe('Ada');
    expect($user->grade_level)->toBe('2A');
});

test('firstname and lastname longer than 255 characters are rejected', function (string $field) {
    config(['members.school_email_domains' => ['ecole.fr']]);
    $user = User::factory()->create([
        'is_internal' => true,
        'firstname' => 'Grace',
        'lastname' => 'Hopper',
    ]);

    $payload = [
        'firstname' => 'Ada',
        'lastname' => 'Lovelace',
        'grade_level' => '1i',
        'school_email' => 'ada@ecole.fr',
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

test('firstname and lastname are optional for external members', function (array $payload) {
    $user = User::factory()->create(['is_external' => true]);

    $response = $this
        ->actingAs($user)
        ->put(route('identity.update'), $payload);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('identity.update'));

    $user->refresh();

    expect($user->firstname)->toBeNull();
    expect($user->lastname)->toBeNull();
})->with([
    'omitted' => [[]],
    'null' => [['firstname' => null, 'lastname' => null]],
]);

test('an external member can save a provided firstname and lastname', function () {
    $user = User::factory()->create(['is_external' => true]);

    $response = $this
        ->actingAs($user)
        ->put(route('identity.update'), [
            'firstname' => 'Grace',
            'lastname' => 'Hopper',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('identity.update'));

    $user->refresh();

    expect($user->firstname)->toBe('Grace');
    expect($user->lastname)->toBe('Hopper');
});

test('grade level and school email are ignored for external members', function () {
    $user = User::factory()->create(['is_external' => true]);

    $response = $this
        ->actingAs($user)
        ->put(route('identity.update'), [
            'firstname' => 'Grace',
            'lastname' => 'Hopper',
            'grade_level' => '1i',
            'school_email' => 'grace@ecole.fr',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('identity.update'));

    $user->refresh();

    expect($user->firstname)->toBe('Grace');
    expect($user->lastname)->toBe('Hopper');
    expect($user->grade_level)->toBeNull();
    expect($user->school_email)->toBeNull();
});

test('member status flags cannot be changed through the endpoint', function (array $currentFlags, array $submittedFlags, bool $expectedInternal, bool $expectedExternal) {
    config(['members.school_email_domains' => ['ecole.fr']]);
    $user = User::factory()->create($currentFlags);

    $response = $this
        ->actingAs($user)
        ->put(route('identity.update'), $submittedFlags + [
            'firstname' => 'Grace',
            'lastname' => 'Hopper',
            'grade_level' => '1i',
            'school_email' => 'grace@ecole.fr',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('identity.update'));

    $user->refresh();

    expect($user->is_internal)->toBe($expectedInternal);
    expect($user->is_external)->toBe($expectedExternal);
})->with([
    'internal stays internal' => [
        ['is_internal' => true, 'is_external' => false],
        ['is_internal' => false, 'is_external' => true],
        true,
        false,
    ],
    'external stays external' => [
        ['is_internal' => false, 'is_external' => true],
        ['is_internal' => true, 'is_external' => false],
        false,
        true,
    ],
]);

test('identity data and school email are stored encrypted while the model returns plain values', function () {
    config(['members.school_email_domains' => ['ecole.fr']]);
    $user = User::factory()->create(['is_internal' => true, 'is_external' => false]);

    $this
        ->actingAs($user)
        ->put(route('identity.update'), [
            'firstname' => 'Grace',
            'lastname' => 'Hopper',
            'grade_level' => '1i',
            'school_email' => 'grace@ecole.fr',
        ])
        ->assertSessionHasNoErrors();

    $raw = DB::table('users')->where('id', $user->id)->sole();

    expect($raw->firstname)->not->toBe('Grace')
        ->not->toContain('Grace');
    expect($raw->lastname)->not->toBe('Hopper')
        ->not->toContain('Hopper');
    expect($raw->grade_level)->not->toBe('1i')
        ->not->toContain('1i');
    expect($raw->school_email)->not->toBe('grace@ecole.fr')
        ->not->toContain('grace@ecole.fr');

    foreach (['is_internal', 'is_external'] as $column) {
        expect($raw->{$column})->not->toBeIn([true, false, 1, 0, '1', '0', 'true', 'false', 't', 'f', 'on', 'off', 'yes', 'no', '']);
    }

    $user->refresh();

    expect($user->firstname)->toBe('Grace');
    expect($user->lastname)->toBe('Hopper');
    expect($user->grade_level)->toBe('1i');
    expect($user->school_email)->toBe('grace@ecole.fr');
    expect($user->is_internal)->toBe(true);
    expect($user->is_external)->toBe(false);
});
