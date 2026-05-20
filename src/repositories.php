<?php

declare(strict_types=1);

use App\Repositories\PlayerRepository;
use App\Repositories\SessionRepository;
use App\Repositories\UserRepository;
use App\Services\TeamDrawService;
use App\Support\Database;

return [
    PlayerRepository::class => static function (Database $database): PlayerRepository {
        return new PlayerRepository($database->pdo());
    },
    SessionRepository::class => static function (Database $database): SessionRepository {
        return new SessionRepository($database->pdo());
    },
    UserRepository::class => static function (Database $database): UserRepository {
        return new UserRepository($database->pdo());
    },
    TeamDrawService::class => static function (): TeamDrawService {
        return new TeamDrawService();
    },
];
