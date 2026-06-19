<?php

use Podio\Client\Auth\PodioCredentials;

test('it exposes the credentials as readonly properties', function () {
    $credentials = new PodioCredentials('client-id', 'client-secret', 'user', 'pass');

    expect($credentials->clientId)->toBe('client-id')
        ->and($credentials->clientSecret)->toBe('client-secret')
        ->and($credentials->username)->toBe('user')
        ->and($credentials->password)->toBe('pass');
});
