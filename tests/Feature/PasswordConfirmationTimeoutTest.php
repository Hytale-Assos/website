<?php

test('the password confirmation timeout is at most one hour', function () {
    expect(config('auth.password_timeout'))
        ->toBeInt()
        ->toBeGreaterThan(0)
        ->toBeLessThanOrEqual(3600);
});
