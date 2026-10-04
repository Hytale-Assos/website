<?php

it('redirects the home page to the login page', function () {
    $response = $this->get('/');

    $response->assertRedirect(route('login'));
});
