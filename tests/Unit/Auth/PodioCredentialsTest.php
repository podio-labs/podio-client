<?php

use Podio\Client\Auth\PodioCredentials;

test('it exposes the client credentials as readonly properties', function () {
    $credentials = new PodioCredentials('client-id', 'client-secret');

    expect($credentials->clientId)->toBe('client-id')
        ->and($credentials->clientSecret)->toBe('client-secret');
});
