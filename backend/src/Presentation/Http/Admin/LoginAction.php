<?php

declare(strict_types=1);

namespace App\Presentation\Http\Admin;

use App\Infrastructure\Database\UserRepository;
use App\Infrastructure\Line\IdTokenVerifier;
use App\Presentation\Http\JsonResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class LoginAction
{
    public function __construct(
        private IdTokenVerifier $verifier,
        private UserRepository $userRepository
    ) {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $params = $request->getParsedBody();
        $idToken = $params['id_token'] ?? '';

        if (empty($idToken)) {
            return JsonResponse::error($response, 'INVALID_REQUEST', 'ID Token is required', null, 400);
        }

        try {
            $claims = $this->verifier->verify($idToken);
        } catch (\RuntimeException $e) {
            error_log('Admin login verifier error: ' . $e->getMessage());
            return JsonResponse::error($response, 'AUTH_FAILED', 'Authentication failed', null, 401);
        }

        try {
            $lineUserId = $claims['sub'];
            $displayName = $claims['name'] ?? '';
            $pictureUrl = $claims['picture'] ?? null;

            $user = $this->userRepository->findByLineUserId($lineUserId);

            if ($user) {
                $this->userRepository->update((int)$user['id'], [
                    'line_display_name' => $displayName,
                    'line_picture_url' => $pictureUrl,
                ]);
                $userId = (int)$user['id'];
            } else {
                $userId = $this->userRepository->save([
                    'line_user_id' => $lineUserId,
                    'line_display_name' => $displayName,
                    'line_picture_url' => $pictureUrl,
                    'role' => 'user',
                ]);
            }

            $user = $this->userRepository->findById($userId);

            // Establish owner session
            if (session_status() !== PHP_SESSION_ACTIVE) {
                throw new \RuntimeException('Session must be started by SessionMiddleware');
            }
            session_regenerate_id(true);
            $_SESSION['owner_user_id'] = $user['id'];
            $_SESSION['owner_authenticated_at'] = time();

            return JsonResponse::success($response, [
                'user' => $user
            ]);
        } catch (\Throwable $e) {
            error_log('Admin login error: ' . $e->getMessage());
            return JsonResponse::error($response, 'AUTH_FAILED', 'Authentication failed', null, 401);
        }
    }
}
