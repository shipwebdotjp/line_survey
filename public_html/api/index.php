<?php

/**
 * API Entry Point
 * Delegates processing to the backend Slim application.
 */

require '/var/www/backend/vendor/autoload.php';

$app = require '/var/www/backend/bootstrap/app.php';
$app->run();
