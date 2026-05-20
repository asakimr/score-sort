<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\HomeController;
use App\Controllers\PlayerController;
use App\Controllers\SessionController;
use App\Middleware\SupervisorMiddleware;
use Slim\App;

return static function (App $app): void {
    $app->get('/', [HomeController::class, 'index']);

    $app->get('/login', [AuthController::class, 'showLogin']);
    $app->post('/login', [AuthController::class, 'login']);
    $app->post('/logout', [AuthController::class, 'logout']);

    $app->get('/peladas/{id}', [SessionController::class, 'show']);

    $app->group('', static function ($group): void {
        $group->get('/jogadores', [PlayerController::class, 'index']);
        $group->post('/jogadores', [PlayerController::class, 'store']);
        $group->get('/jogadores/{id}/editar', [PlayerController::class, 'index']);
        $group->post('/jogadores/{id}', [PlayerController::class, 'update']);
        $group->post('/jogadores/{id}/toggle-active', [PlayerController::class, 'toggleActive']);

        $group->get('/peladas/nova', [SessionController::class, 'create']);
        $group->post('/peladas', [SessionController::class, 'store']);
        $group->post('/peladas/{id}/resortear', [SessionController::class, 'resort']);
        $group->post('/peladas/{id}/partidas/finalizar', [SessionController::class, 'finishMatch']);
    })->add(SupervisorMiddleware::class);
};
