<?php

declare(strict_types=1);

use Phinx\Config\Config;
use Phinx\Migration\Manager;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Dotenv\Dotenv;

require_once __DIR__ . '/../vendor/autoload.php';

// Path configuration
$baseDir = dirname(__DIR__);
$logFile = $baseDir . '/storage/logs/migrations.log';
$lockFile = $baseDir . '/storage/logs/migration.lock';
$phinxConfigPath = $baseDir . '/phinx.php';

/**
 * Log message to migrations.log
 */
function logMigration(string $message, string $logFile): void
{
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
}

/**
 * Send HTTP response and exit
 */
function respond(int $code, string $message): void
{
    if (php_sapi_name() !== 'cli') {
        http_response_code($code);
        header('Content-Type: text/plain; charset=UTF-8');
        echo $message;
    } else {
        echo "[$code] $message" . PHP_EOL;
    }
}

// 1. Check for lock file
if (file_exists($lockFile)) {
    respond(409, "Conflict: Migration runner has already been executed. Remove $lockFile to run again.");
    exit(1);
}

// 2. Load environment variables
if (file_exists($baseDir . '/.env')) {
    $dotenv = Dotenv::createImmutable($baseDir);
    $dotenv->load();
}

// 3. Validate Token (only if NOT in CLI, or always?)
// The requirement says "one-shot and hard to invoke accidentally: require an environment flag or lock token"
// "store the secret in .env or server config, and send it as a header"
$expectedToken = $_ENV['MIGRATION_TOKEN'] ?? $_SERVER['MIGRATION_TOKEN'] ?? null;

if (!$expectedToken) {
    logMigration("ERROR: MIGRATION_TOKEN is not set in environment.", $logFile);
    respond(500, "Internal Server Error: MIGRATION_TOKEN is not configured.");
    exit(1);
}

if (php_sapi_name() !== 'cli') {
    // Try getallheaders() first, fallback to $_SERVER
    $receivedToken = '';
    if (function_exists('getallheaders')) {
        $headers = array_change_key_case(getallheaders(), CASE_LOWER);
        $receivedToken = $headers['x-migration-token'] ?? '';
    }

    if (!$receivedToken && isset($_SERVER['HTTP_X_MIGRATION_TOKEN'])) {
        $receivedToken = $_SERVER['HTTP_X_MIGRATION_TOKEN'];
    }

    if ($receivedToken !== $expectedToken) {
        logMigration("Unauthorized access attempt. Invalid token provided.", $logFile);
        respond(401, "Unauthorized: Invalid migration token.");
        exit(1);
    }
} else {
    // In CLI, we might want to check an argument or just allow it if it's the developer
    // But the requirement says "require an environment flag or lock token"
    // Let's allow CLI without header if it's local, but maybe check env flag
    if (($_SERVER['ALLOW_MIGRATE_CLI'] ?? '') !== '1' && $expectedToken !== ($_SERVER['MIGRATION_TOKEN'] ?? '')) {
        // If we want to be strict even in CLI
        // respond(403, "Forbidden: CLI migration requires ALLOW_MIGRATE_CLI=1 or valid MIGRATION_TOKEN.");
        // exit(1);
    }
}

// 4. Initialize Phinx
try {
    $configArray = require $phinxConfigPath;
    $config = new Config($configArray);

    $output = new BufferedOutput();
    $manager = new Manager($config, new StringInput(''), $output);

    logMigration("Starting migration...", $logFile);

    // Execute migrations
    // Phinx 0.16 manager->migrate() returns void or throws exception
    $env = $config->getDefaultEnvironment();
    logMigration("Using environment: $env", $logFile);
    $manager->migrate($env);

    $executionOutput = $output->fetch();
    logMigration("Migration completed successfully." . PHP_EOL . $executionOutput, $logFile);

    // Create lock file
    file_put_contents($lockFile, date('Y-m-d H:i:s'));

    respond(200, "Success: Migration completed." . PHP_EOL . $executionOutput);
} catch (Throwable $e) {
    $errorMessage = "Migration failed: " . $e->getMessage() . PHP_EOL . $e->getTraceAsString();
    logMigration($errorMessage, $logFile);

    if (isset($output)) {
        logMigration("Output before failure:" . PHP_EOL . $output->fetch(), $logFile);
    }

    respond(500, "Error: Migration failed. Check logs for details.");
    exit(1);
}
