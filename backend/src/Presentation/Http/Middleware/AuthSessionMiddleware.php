<?php

declare(strict_types=1);

namespace App\Presentation\Http\Middleware;

use App\Infrastructure\Database\RespondentRepository;
use App\Presentation\Http\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ResponseFactory;

final class AuthSessionMiddleware implements MiddlewareInterface
{
    public function __construct(
        private RespondentRepository $respondentRepository,
        private ResponseFactory $responseFactory
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $respondentId = $_SESSION['respondent_id'] ?? null;

        if (!$respondentId) {
            $response = $this->responseFactory->createResponse(401);
            return JsonResponse::error($response, 'SESSION_REQUIRED', 'Session is required', null, 401);
        }

        $respondent = $this->respondentRepository->findById((int)$respondentId);

        if (!$respondent) {
            // Session exists but respondent was deleted?
            unset($_SESSION['respondent_id'], $_SESSION['authenticated_at']);
            $response = $this->responseFactory->createResponse(401);
            return JsonResponse::error($response, 'SESSION_REQUIRED', 'Invalid session', null, 401);
        }

        // Attach respondent to request
        $request = $request->withAttribute('respondent', $respondent);

        return $handler->handle($request);
    }
}
