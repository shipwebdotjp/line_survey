<?php

use App\Config\Settings;
use App\Infrastructure\Database\ConnectionFactory;
use DI\ContainerBuilder;
use Dotenv\Dotenv;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\ConnectionInterface;
use Psr\Container\ContainerInterface;
use Slim\App;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}

$settings = new Settings();
date_default_timezone_set($settings->get('app.timezone', 'Asia/Tokyo'));

// Instantiate PHP-DI ContainerBuilder
$containerBuilder = new ContainerBuilder();

// Set up settings
$containerBuilder->addDefinitions([
    Settings::class => function () use ($settings) {
        return $settings;
    },
    ConnectionFactory::class => function () use ($settings) {
        return new ConnectionFactory($settings);
    },
    Capsule::class => function (ContainerInterface $container) {
        return $container->get(ConnectionFactory::class)->create();
    },
    ConnectionInterface::class => function (ContainerInterface $container) {
        return $container->get(Capsule::class)->getConnection();
    },
]);

// Build PHP-DI Container instance
$container = $containerBuilder->build();

// Instantiate the app
AppFactory::setContainer($container);
$app = AppFactory::create();

// Register middleware
$middleware = require __DIR__ . '/middleware.php';
$middleware($app);

// Register routes
$routes = require __DIR__ . '/../routes/api.php';
$routes($app);

return $app;
