<?php

declare(strict_types=1);

use App\Application\Settings;
use App\Middleware\SupervisorMiddleware;
use App\Repositories\UserRepository;
use App\Support\Auth;
use App\Support\Database;
use App\Support\View;
use Psr\Http\Message\ResponseFactoryInterface;
use Slim\Factory\AppFactory;

return [
    Database::class => static function (Settings $settings): Database {
        return new Database($settings->get('db.database'));
    },
    View::class => static function (Settings $settings): View {
        return new View(dirname(__DIR__) . '/views', [
            'appName' => $settings->get('app.name', 'Pelada Manager'),
            'authUser' => $_SESSION['auth_user'] ?? null,
        ]);
    },
    ResponseFactoryInterface::class => static function () {
        return AppFactory::determineResponseFactory();
    },
    Auth::class => static function (UserRepository $users): Auth {
        return new Auth($users);
    },
    SupervisorMiddleware::class => static function (Auth $auth, ResponseFactoryInterface $responseFactory): SupervisorMiddleware {
        return new SupervisorMiddleware($auth, $responseFactory);
    },
];
