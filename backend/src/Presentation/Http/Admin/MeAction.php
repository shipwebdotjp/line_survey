<?php

declare(strict_types=1);

namespace App\Presentation\Http\Admin;

use App\Presentation\Http\JsonResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class MeAction
{
    public function __invoke(Request $request, Response $response): Response
    {
        $user = $request->getAttribute('owner_user');

        return JsonResponse::success($response, [
            'user' => [
                'id' => $user['id'],
                'line_user_id' => $user['line_user_id'],
                'line_display_name' => $user['line_display_name'],
                'line_picture_url' => $user['line_picture_url'],
                'role' => $user['role'],
                'created_at' => $user['created_at'],
                'updated_at' => $user['updated_at'],
            ]
        ]);
    }
}
