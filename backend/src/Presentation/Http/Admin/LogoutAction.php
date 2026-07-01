<?php

declare(strict_types=1);

namespace App\Presentation\Http\Admin;

use App\Presentation\Http\JsonResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class LogoutAction
{
    public function __invoke(Request $request, Response $response): Response
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            unset($_SESSION['owner_user_id'], $_SESSION['owner_authenticated_at']);
            // Note: We don't session_destroy() here because there might be a respondent session
            // or other session data we want to keep if they share the same session cookie.
        }

        return JsonResponse::success($response, ['message' => 'Logged out successfully']);
    }
}
