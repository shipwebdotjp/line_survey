<?php

declare(strict_types=1);

namespace App\Presentation\Http\Admin\Survey;

use App\Application\Admin\Survey\ListResponseDraftsUseCase;
use App\Presentation\Http\JsonResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class ListResponseDraftsAction
{
    public function __construct(
        private ListResponseDraftsUseCase $useCase
    ) {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $ownerUser = $request->getAttribute('owner_user');
        $drafts = $this->useCase->execute((int)$ownerUser['id']);
        return JsonResponse::success($response, ['drafts' => $drafts]);
    }
}
