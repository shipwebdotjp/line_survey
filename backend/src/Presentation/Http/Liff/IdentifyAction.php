<?php

declare(strict_types=1);

namespace App\Presentation\Http\Liff;

use App\Application\Respondent\IdentifyService;
use App\Infrastructure\Database\SurveyRepository;
use App\Infrastructure\Line\IdTokenVerifier;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class IdentifyAction
{
    public function __construct(
        private IdTokenVerifier $verifier,
        private IdentifyService $identifyService,
        private SurveyRepository $surveyRepository
    ) {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $params = $request->getParsedBody();
        $idToken = $params['id_token'] ?? '';
        $publicId = $params['public_id'] ?? '';

        if (empty($idToken)) {
            $response->getBody()->write(json_encode(['error' => 'ID Token is required'], JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        if (empty($publicId)) {
            $response->getBody()->write(json_encode(['error' => 'public_id is required'], JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            $claims = $this->verifier->verify($idToken);
        } catch (\RuntimeException $e) {
            // Assume verification failures throw RuntimeException from verifier
            $response->getBody()->write(json_encode([
                'error' => 'ID Token verification failed',
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        try {
            $survey = $this->surveyRepository->findByPublicId($publicId);
            if (!$survey) {
                $response->getBody()->write(json_encode(['error' => 'Survey not found'], JSON_UNESCAPED_UNICODE));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            $result = $this->identifyService->identify($claims['sub'], $claims['name'], (int)$survey['owner_user_id']);

            // Establish session on success
            if (isset($result['respondent']['id'])) {
                if (session_status() !== PHP_SESSION_ACTIVE) {
                    throw new \RuntimeException('Session must be started by SessionMiddleware');
                }
                session_regenerate_id(true);
                $_SESSION['respondent_id'] = $result['respondent']['id'];
                $_SESSION['authenticated_at'] = time();
            }

            $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (\InvalidArgumentException $e) {
            $response->getBody()->write(json_encode([
                'error' => 'Validation failed',
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        } catch (\Throwable $e) {
            $response->getBody()->write(json_encode([
                'error' => 'Internal server error during identification',
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}
