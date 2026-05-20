<?php

declare(strict_types=1);

use App\Application\Settings;

return [
    Settings::class => static function (): Settings {
        $envPath = dirname(__DIR__) . '/.env';
        $env = file_exists($envPath) ? parse_ini_file($envPath, false, INI_SCANNER_TYPED) : [];

        return new Settings([
            'app' => [
                'name' => $env['APP_NAME'] ?? 'Pelada Manager',
                'env' => $env['APP_ENV'] ?? 'local',
                'debug' => $env['APP_DEBUG'] ?? true,
                'url' => $env['APP_URL'] ?? 'http://localhost:8500',
            ],
            'db' => [
                'driver' => $env['DB_DRIVER'] ?? 'sqlite',
                'database' => dirname(__DIR__) . '/' . ($env['DB_DATABASE'] ?? 'storage/database.sqlite'),
            ],
        ]);
    },
];
