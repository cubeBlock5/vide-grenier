<?php

/**
 * Génère public/openapi.json à partir des attributs OpenAPI présents dans App/.
 *
 * Usage : php bin/generate-openapi.php
 * Lancé automatiquement après chaque `composer install`/`update` (cf. composer.json).
 */

$root = dirname(__DIR__);
$openapiBin = $root . '/vendor/bin/openapi';
$source = $root . '/App';
$output = $root . '/public/openapi.json';

$command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($openapiBin)
    . ' ' . escapeshellarg($source)
    . ' --output ' . escapeshellarg($output);

passthru($command, $exitCode);

exit($exitCode);
