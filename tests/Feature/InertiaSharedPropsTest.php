<?php

it('shares the application timezone on inertia responses', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('app.timezone', config('app.timezone'))
        );
});

it('reflects a runtime change to the application timezone', function () {
    config()->set('app.timezone', 'Europe/Paris');

    $this->get(route('login'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('app.timezone', 'Europe/Paris')
        );
});
