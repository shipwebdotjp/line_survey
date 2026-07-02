<?php

declare(strict_types=1);

namespace App\Presentation\Http\Middleware;

use App\Infrastructure\Database\UserRepository;
use App\Presentation\Http\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ResponseFactory;

final class AdminAuthMiddleware implements MiddlewareInterface
{
    public function __construct(
        private UserRepository $userRepository,
        private ResponseFactory $responseFactory
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $ownerUserId = $_SESSION['owner_user_id'] ?? null;

        if (!$ownerUserId) {
            $response = $this->responseFactory->createResponse(401);
            return JsonResponse::error($response, 'OWNER_SESSION_REQUIRED', 'Manage session is required', null, 401);
        }

        $user = $this->userRepository->findById((int)$ownerUserId);

        if (!$user) {
            unset($_SESSION['owner_user_id'], $_SESSION['owner_authenticated_at']);
            $response = $this->responseFactory->createResponse(401);
            return JsonResponse::error($response, 'OWNER_SESSION_REQUIRED', 'Invalid owner session', null, 401);
        }

        if (($user['role'] ?? '') !== 'admin') {
            $response = $this->responseFactory->createResponse(403);
            return JsonResponse::error($response, 'FORBIDDEN', 'Access denied', null, 403);
        }

        // Attach owner user to request
        $request = $request->withAttribute('owner_user', $user);

        return $handler->handle($request);
    }
}
