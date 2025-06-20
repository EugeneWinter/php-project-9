<?php

declare(strict_types=1);

namespace App\Presentation\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use App\Domain\Models\Url;
use App\Infrastructure\Persistence\UrlRepository;
use RuntimeException;

class UrlController
{
    public function __construct(
        private \Slim\Views\PhpRenderer $renderer,
        private UrlRepository $urlRepository
    ) {
    }

    public function store(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $request->getParsedBody();
        $url = new Url(0, $data['url']['name'], '');
        $savedUrl = $this->urlRepository->save($url);

        return $response
            ->withHeader('Location', '/urls/' . $savedUrl->getId())
            ->withStatus(302);
    }

    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $url = $this->urlRepository->findById((int)$args['id']);
        if (!$url) {
            return $response->withStatus(404);
        }

        try {
            return $this->renderer->render($response, 'urls/show.phtml', ['url' => $url]);
        } catch (\Exception $e) {
            error_log("Rendering error. Base path: " . $this->renderer->getTemplatePath());
            error_log("Template contents: " . file_get_contents('/app/templates/urls/show.phtml'));
            throw new RuntimeException("Rendering failed: " . $e->getMessage());
        }
    }
}
