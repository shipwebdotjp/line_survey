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
            'user' => $user
        ]);
    }
}
