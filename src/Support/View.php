<?php

declare(strict_types=1);

namespace App\Support;

use Psr\Http\Message\ResponseInterface;

final class View
{
    public function __construct(
        private readonly string $basePath,
        private readonly array $shared = []
    ) {
    }

    public function render(ResponseInterface $response, string $template, array $data = [], ?string $layout = 'layouts/app'): ResponseInterface
    {
        $payload = array_merge($this->shared, $data);
        $content = $this->capture($template, $payload);

        if ($layout !== null) {
            $payload['content'] = $content;
            $output = $this->capture($layout, $payload);
        } else {
            $output = $content;
        }

        $response->getBody()->write($output);

        return $response;
    }

    private function capture(string $template, array $data): string
    {
        extract($data, EXTR_SKIP);

        ob_start();
        require $this->basePath . '/' . $template . '.php';

        return (string) ob_get_clean();
    }
}
