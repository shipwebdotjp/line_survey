<?php

declare(strict_types=1);

namespace App\Presentation\Http\Admin\RespondentMaster;

use App\Application\Admin\RespondentMaster\ListRespondentMastersUseCase;
use App\Presentation\Http\JsonResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class ListRespondentMastersAction
{
    public function __construct(
        private ListRespondentMastersUseCase $useCase
    ) {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $ownerUser = $request->getAttribute('owner_user');
        if (!is_array($ownerUser) || !isset($ownerUser['id']) || (int)$ownerUser['id'] <= 0) {
            return JsonResponse::error($response, 'OWNER_SESSION_REQUIRED', 'Admin session is required', null, 401);
        }

        $masters = $this->useCase->execute((int)$ownerUser['id']);
        return JsonResponse::success($response, $masters);
    }
}
