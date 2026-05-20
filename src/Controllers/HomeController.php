<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\PlayerRepository;
use App\Repositories\SessionRepository;
use App\Support\View;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class HomeController
{
    public function __construct(
        private readonly View $view,
        private readonly PlayerRepository $players,
        private readonly SessionRepository $sessions
    ) {
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->view->render($response, 'pages/home', [
            'title' => 'Dashboard',
            'headerTitle' => 'Painel da pelada',
            'activeNav' => 'home',
            'stats' => [
                'activePlayers' => $this->players->countActive(),
                'presenceConfirmed' => $this->sessions->countPresentInLatestSession(),
                'waitingTeams' => $this->sessions->countWaitingTeamsInLatestSession(),
            ],
            'latestSession' => $this->sessions->latest(),
            'players' => array_slice($this->players->all(), 0, 8),
        ]);
    }
}
