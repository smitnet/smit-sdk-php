<?php

require_once __DIR__ . '/includes/config.php';

return $client->login(['name'], 'http://my-development-domain.test/private.php');
