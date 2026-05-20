<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Support\Auth;
use App\Support\View;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class AuthController
{
    public function __construct(
        private readonly View $view,
        private readonly Auth $auth
    ) {
    }

    public function showLogin(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if ($this->auth->check()) {
            return $this->redirect($response, '/');
        }

        $flash = $_SESSION['flash'] ?? null;
        $errors = $_SESSION['auth_errors'] ?? [];
        $old = $_SESSION['old_auth'] ?? [];

        unset($_SESSION['flash'], $_SESSION['auth_errors'], $_SESSION['old_auth']);

        return $this->view->render($response, 'pages/auth/login', [
            'title' => 'Entrar',
            'headerTitle' => 'Acesso administrativo',
            'activeNav' => 'login',
            'flash' => $flash,
            'errors' => $errors,
            'old' => $old,
        ]);
    }

    public function login(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $input = $this->normalizeInput((array) $request->getParsedBody());
        $errors = $this->validate($input);

        if ($errors === [] && !$this->auth->attempt($input['username'], $input['password'])) {
            $errors['general'] = 'Usuário ou senha inválidos.';
        }

        if ($errors !== []) {
            $_SESSION['auth_errors'] = $errors;
            $_SESSION['old_auth'] = ['username' => $input['username']];

            return $this->redirect($response, '/login');
        }

        $_SESSION['flash'] = [
            'type' => 'success',
            'message' => 'Login realizado com sucesso.',
        ];

        return $this->redirect($response, '/');
    }

    public function logout(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->auth->logout();
        $_SESSION['flash'] = [
            'type' => 'success',
            'message' => 'Você saiu da área administrativa.',
        ];

        return $this->redirect($response, '/login');
    }

    private function normalizeInput(array $payload): array
    {
        return [
            'username' => trim((string) ($payload['username'] ?? '')),
            'password' => (string) ($payload['password'] ?? ''),
        ];
    }

    private function validate(array $input): array
    {
        $errors = [];

        if ($input['username'] === '') {
            $errors['username'] = 'Informe o usuário.';
        }

        if ($input['password'] === '') {
            $errors['password'] = 'Informe a senha.';
        }

        return $errors;
    }

    private function redirect(ResponseInterface $response, string $path): ResponseInterface
    {
        return $response
            ->withHeader('Location', $path)
            ->withStatus(302);
    }
}
