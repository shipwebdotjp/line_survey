<?php

declare(strict_types=1);

namespace App\Presentation\Http\Liff;

use App\Application\Respondent\IdentifyService;
use App\Infrastructure\Line\IdTokenVerifier;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class IdentifyAction
{
    public function __construct(
        private IdTokenVerifier $verifier,
        private IdentifyService $identifyService
    ) {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $params = $request->getParsedBody();
        $idToken = $params['id_token'] ?? '';

        if (empty($idToken)) {
            $response->getBody()->write(json_encode(['error' => 'ID Token is required'], JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            $claims = $this->verifier->verify($idToken);
            $result = $this->identifyService->identify($claims['sub'], $claims['name']);

            $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'error' => 'Identification failed',
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }
    }
}
