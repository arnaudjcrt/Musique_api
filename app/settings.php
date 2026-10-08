<?php

declare(strict_types=1);

use App\Application\Settings\Settings;
use App\Application\Settings\SettingsInterface;
use DI\ContainerBuilder;
use Monolog\Logger;

return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([
        SettingsInterface::class => function () {
            return new Settings([
                'displayErrorDetails' => true,
                'logError' => false,
                'logErrorDetails' => false,

                'logger' => [
                    'name' => 'slim-app',
                    'path' => isset($_ENV['docker'])
                        ? 'php://stdout'
                        : __DIR__ . '/../logs/app.log',
                    'level' => Logger::DEBUG,
                ],

                'db' => [
                    'driver' => 'mysql',
                    'host' => 'mysql-arnaudddddd.alwaysdata.net',
                    'username' => 'arnaudddddd',
                    'database' => 'arnaudddddd_music',
                    'password' => 'Nono05500!',
                    'charset' => 'utf8mb4',
                    'collation' => 'utf8mb4_unicode_ci',

                    'flags' => [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    ],
                ],

            ]);
        },
    ]);
};