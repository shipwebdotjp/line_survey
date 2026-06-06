<?php

/**
 * Backend Internal Entry Point (for testing or direct access if configured)
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->run();
