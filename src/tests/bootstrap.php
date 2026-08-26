<?php

require dirname(__DIR__) . '/vendor/autoload.php';

// $_SESSION is used as a plain array by the app (Flash, auth, ...). The CLI
// SAPI never starts a real session, so we just seed the superglobal by hand.
$_SESSION = [];
