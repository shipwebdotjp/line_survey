<?php

declare(strict_types=1);

namespace App\Presentation\Http\Liff;

use App\Application\Respondent\IdentifyService;
use App\Infrastructure\Line\IdTokenVerifier;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class IdentifyManualAction
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
        $name = $params['name'] ?? '';
        $email = $params['email'] ?? '';
        $honorific = $params['honorific'] ?? '';

        if (empty($idToken) || empty($name) || empty($email)) {
            $response->getBody()->write(json_encode(['error' => 'ID Token, name and email are required'], JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            $claims = $this->verifier->verify($idToken);
            $respondent = $this->identifyService->saveManual($claims['sub'], $claims['name'], [
                'name' => $name,
                'email' => $email,
                'honorific' => $honorific
            ]);

            $response->getBody()->write(json_encode([
                'status' => IdentifyService::STATUS_EXISTING, // After manual save, it's treated as existing
                'respondent' => $respondent
            ], JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'error' => 'Manual identification failed',
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }
    }
}
