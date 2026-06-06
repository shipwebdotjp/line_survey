<?php

namespace App\Infrastructure\Database;

use App\Config\Settings;
use Illuminate\Database\Capsule\Manager as Capsule;

final class ConnectionFactory
{
    public function __construct(private Settings $settings)
    {
    }

    public function create(): Capsule
    {
        $capsule = new Capsule();

        $capsule->addConnection([
            'driver' => $this->settings->get('db.driver', 'mysql'),
            'host' => $this->settings->get('db.host', 'localhost'),
            'port' => $this->settings->get('db.port', 3306),
            'database' => $this->settings->get('db.database', ''),
            'username' => $this->settings->get('db.username', ''),
            'password' => $this->settings->get('db.password', ''),
            'charset' => $this->settings->get('db.charset', 'utf8mb4'),
            'collation' => $this->settings->get('db.collation', 'utf8mb4_unicode_ci'),
            'prefix' => '',
            'strict' => true,
        ]);

        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        return $capsule;
    }
}
