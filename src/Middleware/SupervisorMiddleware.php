<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Support\Auth;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class SupervisorMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly Auth $auth,
        private readonly ResponseFactoryInterface $responseFactory
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->auth->isSupervisor()) {
            return $handler->handle($request);
        }

        $_SESSION['flash'] = [
            'type' => 'error',
            'message' => $this->auth->check()
                ? 'Seu usuário não tem permissão de supervisor.'
                : 'Faça login como supervisor para acessar essa área.',
        ];

        $target = $this->auth->check() ? '/' : '/login';
        $response = $this->responseFactory->createResponse(302);

        return $response->withHeader('Location', $target);
    }
}
