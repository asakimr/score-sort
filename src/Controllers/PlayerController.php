<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\PlayerRepository;
use App\Support\View;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class PlayerController
{
    public function __construct(
        private readonly View $view,
        private readonly PlayerRepository $players
    ) {
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response, array $args = []): ResponseInterface
    {
        $editingPlayer = null;
        $playerId = isset($args['id']) ? (int) $args['id'] : 0;

        if ($playerId > 0) {
            $editingPlayer = $this->players->findById($playerId);

            if ($editingPlayer === null) {
                $this->flash('error', 'Jogador não encontrado.');

                return $this->redirect($response, '/jogadores');
            }
        }

        $old = $_SESSION['old_player_form'] ?? [];
        $errors = $_SESSION['player_form_errors'] ?? [];
        $flash = $_SESSION['flash'] ?? null;

        unset($_SESSION['old_player_form'], $_SESSION['player_form_errors'], $_SESSION['flash']);

        return $this->view->render($response, 'pages/players/index', [
            'title' => 'Jogadores',
            'headerTitle' => 'Jogadores',
            'activeNav' => 'players',
            'players' => $this->players->all(),
            'editingPlayer' => $editingPlayer,
            'old' => $old,
            'errors' => $errors,
            'flash' => $flash,
        ]);
    }

    public function store(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $input = $this->normalizeInput((array) $request->getParsedBody());
        $errors = $this->validate($input);

        if ($errors !== []) {
            $_SESSION['old_player_form'] = $input;
            $_SESSION['player_form_errors'] = $errors;

            return $this->redirect($response, '/jogadores');
        }

        $this->players->create($input);
        $this->flash('success', 'Jogador cadastrado com sucesso.');

        return $this->redirect($response, '/jogadores');
    }

    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $playerId = (int) ($args['id'] ?? 0);
        $player = $this->players->findById($playerId);

        if ($player === null) {
            $this->flash('error', 'Jogador não encontrado.');

            return $this->redirect($response, '/jogadores');
        }

        $input = $this->normalizeInput((array) $request->getParsedBody());
        $errors = $this->validate($input);

        if ($errors !== []) {
            $_SESSION['old_player_form'] = $input;
            $_SESSION['player_form_errors'] = $errors;

            return $this->redirect($response, '/jogadores/' . $playerId . '/editar');
        }

        $this->players->update($playerId, $input);
        $this->flash('success', 'Jogador atualizado com sucesso.');

        return $this->redirect($response, '/jogadores');
    }

    public function toggleActive(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $playerId = (int) ($args['id'] ?? 0);
        $player = $this->players->findById($playerId);

        if ($player === null) {
            $this->flash('error', 'Jogador não encontrado.');

            return $this->redirect($response, '/jogadores');
        }

        $this->players->toggleActive($playerId);
        $this->flash('success', sprintf('Status de %s atualizado.', $player['name']));

        return $this->redirect($response, '/jogadores');
    }

    private function normalizeInput(array $payload): array
    {
        $name = trim((string) ($payload['name'] ?? ''));
        $rating = str_replace(',', '.', trim((string) ($payload['rating'] ?? '0')));

        return [
            'name' => $name,
            'rating' => $rating,
            'is_goalkeeper' => isset($payload['is_goalkeeper']) ? 1 : 0,
            'is_active' => isset($payload['is_active']) ? 1 : 0,
        ];
    }

    private function validate(array $input): array
    {
        $errors = [];

        if ($input['name'] === '') {
            $errors['name'] = 'Informe o nome do jogador.';
        } elseif (mb_strlen($input['name']) > 80) {
            $errors['name'] = 'O nome deve ter no máximo 80 caracteres.';
        }

        if (!is_numeric($input['rating'])) {
            $errors['rating'] = 'A nota deve ser numérica.';
        } else {
            $rating = (float) $input['rating'];
            $scaled = (int) round($rating * 2);

            if ($rating < 0 || $rating > 5) {
                $errors['rating'] = 'A nota deve estar entre 0 e 5.';
            } elseif (abs(($rating * 2) - $scaled) > 0.00001) {
                $errors['rating'] = 'A nota deve usar incrementos de 0,5.';
            }
        }

        return $errors;
    }

    private function flash(string $type, string $message): void
    {
        $_SESSION['flash'] = [
            'type' => $type,
            'message' => $message,
        ];
    }

    private function redirect(ResponseInterface $response, string $path): ResponseInterface
    {
        return $response
            ->withHeader('Location', $path)
            ->withStatus(302);
    }
}
