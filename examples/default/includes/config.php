<?php

require_once __DIR__ . '/vendor/autoload.php';

$client = new \SMIT\SDK\Auth\Auth([
    'domain' => 'remote-authorization-domain.com',
    'client_id' => 'abc123',
    'client_secret' => 'xyz123',
    'redirect_uri' => 'http://my-development-domain.test/callback.php',
]);
