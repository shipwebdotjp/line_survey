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

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $response->getBody()->write(json_encode(['error' => 'Invalid email format'], JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            $claims = $this->verifier->verify($idToken);
        } catch (\RuntimeException $e) {
            $response->getBody()->write(json_encode([
                'error' => 'ID Token verification failed',
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        try {
            $respondent = $this->identifyService->saveManual($claims['sub'], $claims['name'], [
                'name' => $name,
                'email' => $email,
                'honorific' => $honorific
            ]);

            // Establish session on success
            if (isset($respondent['id'])) {
                if (session_status() !== PHP_SESSION_ACTIVE) {
                    throw new \RuntimeException('Session must be started by SessionMiddleware');
                }
                session_regenerate_id(true);
                $_SESSION['respondent_id'] = $respondent['id'];
                $_SESSION['authenticated_at'] = time();
            }

            $response->getBody()->write(json_encode([
                'status' => IdentifyService::STATUS_MANUAL_SAVED,
                'respondent' => $respondent
            ], JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (\InvalidArgumentException $e) {
            $response->getBody()->write(json_encode([
                'error' => 'Validation failed',
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        } catch (\Throwable $e) {
            $response->getBody()->write(json_encode([
                'error' => 'Internal server error during manual save',
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}
