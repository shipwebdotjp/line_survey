<?php

declare(strict_types=1);

use Phinx\Config\Config;
use Phinx\Migration\Manager;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Dotenv\Dotenv;
use Dotenv\Exception\ExceptionInterface as DotenvException;

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
    // Ensure log directory exists
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
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

try {
    // 1. Load environment variables
    if (file_exists($baseDir . '/.env')) {
        try {
            $dotenv = Dotenv::createImmutable($baseDir);
            $dotenv->load();
        } catch (DotenvException $e) {
            logMigration("ERROR: Failed to load .env: " . $e->getMessage(), $logFile);
            respond(500, "Internal Server Error: Environment configuration failure.");
            exit(1);
        }
    }

    // 2. Validate Token
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

        // Use timing-safe comparison
        if (!hash_equals($expectedToken, $receivedToken)) {
            logMigration("Unauthorized access attempt. Invalid token provided.", $logFile);
            respond(401, "Unauthorized: Invalid migration token.");
            exit(1);
        }
    } else {
        // Active CLI gating
        $allowCli = ($_SERVER['ALLOW_MIGRATE_CLI'] ?? '') === '1';
        $cliTokenMatches = hash_equals($expectedToken, $_SERVER['MIGRATION_TOKEN'] ?? '');

        if (!$allowCli && !$cliTokenMatches) {
            logMigration("CLI migration rejected. ALLOW_MIGRATE_CLI is not 1 and MIGRATION_TOKEN doesn't match.", $logFile);
            respond(403, "Forbidden: CLI migration requires ALLOW_MIGRATE_CLI=1 or valid MIGRATION_TOKEN.");
            exit(1);
        }
    }

    // 3. Atomic lock acquisition
    // Open for writing only; place the file pointer at the beginning of the file and truncate it to zero length.
    // If the file exists, the fopen() call fails by returning FALSE and generating an error of level E_WARNING.
    $fp = @fopen($lockFile, 'x');
    if ($fp === false) {
        respond(409, "Conflict: Migration runner has already been executed or is running. Remove $lockFile to run again.");
        exit(1);
    }
    // Write the timestamp into the lock file
    fwrite($fp, date('Y-m-d H:i:s'));
    fclose($fp);

    // 4. Initialize Phinx
    $configArray = require $phinxConfigPath;
    $config = new Config($configArray);

    $output = new BufferedOutput();
    $manager = new Manager($config, new StringInput(''), $output);

    logMigration("Starting migration...", $logFile);

    // Execute migrations
    $env = $config->getDefaultEnvironment();
    logMigration("Using environment: $env", $logFile);
    $manager->migrate($env);

    $executionOutput = $output->fetch();
    logMigration("Migration completed successfully." . PHP_EOL . $executionOutput, $logFile);

    respond(200, "Success: Migration completed." . PHP_EOL . $executionOutput);

} catch (Throwable $e) {
    $errorMessage = "Migration failed: " . $e->getMessage() . PHP_EOL . $e->getTraceAsString();
    logMigration($errorMessage, $logFile);

    if (isset($output)) {
        logMigration("Output before failure:" . PHP_EOL . $output->fetch(), $logFile);
    }

    // If we failed BEFORE or DURING migration, we might want to keep the lock
    // to prevent further attempts until manually checked.
    // The requirement says "one-shot and hard to invoke accidentally".

    respond(500, "Error: Migration failed. Check logs for details.");
    exit(1);
}
