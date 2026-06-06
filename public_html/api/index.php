<?php

/**
 * API Entry Point
 * Delegates processing to the backend Slim application.
 */

require __DIR__ . '/../../backend/vendor/autoload.php';

$app = require __DIR__ . '/../../backend/bootstrap/app.php';
$app->run();
