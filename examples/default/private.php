<?php

require_once __DIR__ . '/includes/config.php';

$client->login(['name']);
?>

<h1>Private page</h1>

<p>
    Authorization required
</p>

<h2>User object</h2>
<pre>
    <?php var_dump($client->user()); ?>
</pre>

<hr />

<a href="/logout.php">Logout</a>
